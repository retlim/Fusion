<?php
/**
 * Fusion - PHP Package Manager
 * Copyright © Valvoid
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Valvoid\Fusion\Tasks\Download;

use Exception;
use PharData;
use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Dir\Proxy as DirProxy;
use Valvoid\Fusion\Group\Group;
use Valvoid\Fusion\Hub\Proxy\Proxy as HubProxy;
use Valvoid\Fusion\Hub\Responses\Cache\Archive;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Log\Events\Errors\Request;
use Valvoid\Fusion\Log\Events\Event;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Events\Interceptor;
use Valvoid\Fusion\Log\Proxy as LogProxy;
use Valvoid\Fusion\Metadata\External\Category as ExternalMetaCategory;
use Valvoid\Fusion\Metadata\External\External as ExternalMeta;
use Valvoid\Fusion\Tasks\Task;
use Valvoid\Fusion\Wrappers\Dir;
use Valvoid\Fusion\Wrappers\Extension;
use Valvoid\Fusion\Wrappers\File;
use ZipArchive;

/**
 * Download task to fetch external packages.
 *
 * @Copyright Valvoid
 * @license GNU GPLv3
 */
class Download extends Task implements Interceptor
{
    /** @var string Task cache directory. */
    private string $taskDir;

    /** @var string Packages cache directory. */
    private string $packagesDir;

    /** @var array<int, ExternalMeta> External metadata queue. */
    private array $metas;

    /**
     * Constructs the task.
     *
     * @param Box $box Dependency injection container.
     * @param LogProxy $log Event log.
     * @param DirProxy $directory Current working directory.
     * @param Extension $extension Standard extension logic wrapper.
     * @param File $file Standard file logic wrapper.
     * @param Dir $dir Standard dir logic wrapper.
     * @param array $config Task config.
     */
    public function __construct(
        private readonly Box $box,
        private readonly Group $group,
        private readonly LogProxy $log,
        private readonly HubProxy $hub,
        private readonly DirProxy $directory,
        private readonly Extension $extension,
        private readonly File $file,
        private readonly Dir $dir,
        array $config)
    {
        parent::__construct($config);
    }

    /**
     * Executes the task.
     *
     * @throws Error Internal exception.
     * @throws Request Request exception.
     */
    public function execute(): void
    {
        $this->log->info("cache external packages");

        if (!$this->group->hasDownloadable())
            return;

        $this->packagesDir = $this->directory->getPackagesDir();
        $this->taskDir = $this->directory->getTaskDir() .
            "/" . $this->config["id"];

        // enqueue all for parallel download
        foreach ($this->group->getExternalMetas() as $metadata)
            if ($metadata->getCategory() == ExternalMetaCategory::DOWNLOADABLE) {
                $id = $this->hub->addArchiveRequest($metadata->getSource());
                $this->metas[$id] = $metadata;
            }

        // download and
        // notify when done
        $this->hub->executeRequests(

            // recommended zip or
            // higher memory usage phar data
            $this->extension->loaded("zip") ?
                $this->extractZipArchive(...) :
                $this->extractPharData(...)
        );
    }

    /**
     * Extracts zip archive to common state cache directory.
     *
     * @param Archive $response Archive response.
     * @throws Error Internal exception.
     */
    private function extractZipArchive(Archive $response): void
    {
        $file = $response->getFile();
        $archive = $this->box->get(ZipArchive::class);
        $status = $archive->open($file);

        if ($status !== true)
            throw new Error(
                "Can't open the archive file \"$file\". " .

                // no status string here
                // create self
                match ($status) {
                    ZipArchive::ER_EXISTS => "File already exists.",
                    ZipArchive::ER_INCONS => "Zip archive inconsistent.",
                    ZipArchive::ER_INVAL => "Invalid argument.",
                    ZipArchive::ER_MEMORY => "Malloc failure.",
                    ZipArchive::ER_NOENT => "No such file.",
                    ZipArchive::ER_NOZIP => "Not a zip archive.",
                    ZipArchive::ER_OPEN => "Can't open file.",
                    ZipArchive::ER_READ => "Read error.",
                    ZipArchive::ER_SEEK => "Seek error.",

                    // false or whatever
                    default => "Unknown error."
                });

        $metadata = $this->metas[$response->getId()];
        $id = $metadata->getId();
        $from = "$this->taskDir/$id";
        $to = "$this->packagesDir/$id";

        if (!$archive->extractTo($from))
            throw new Error(
                "Can't extract the archive file \"$file\". " .
                $archive->getStatusString()
            );

        if (!$archive->close())
            throw new Error(
                "Can't close the archive file \"$file\". " .
                $archive->getStatusString()
            );

        $this->directory->createDir($to);

        // validate/normalize
        // get root directory
        $from = $this->getNormalizedFromDir($from, $file);

        $this->directory->rename($from, $to);
        $this->addBotLayer($to, $metadata->getLayers());
        $this->log->info(
            $this->box->get(Content::class,
                content: $metadata->getContent()));
    }

    /**
     * Extracts phar data archive to common state cache directory.
     *
     * @param Archive $response Archive response.
     * @throws Error Internal exception.
     */
    private function extractPharData(Archive $response): void
    {
        $file = $response->getFile();

        try {
            $metadata = $this->metas[$response->getId()];
            $id = $metadata->getId();
            $from = "$this->taskDir/$id";
            $to = "$this->packagesDir/$id";
            $archive = $this->box->get(PharData::class,
                filename: $file);

            $archive->extractTo($from, null, true);

            $this->directory->createDir($to);

            // validate/normalize
            // get root directory
            $from = $this->getNormalizedFromDir($from, $file);

            $this->directory->rename($from, $to);
            $this->addBotLayer($to, $metadata->getLayers());
            $this->log->info(
                $this->box->get(Content::class,
                    content: $metadata->getContent()));

        } catch (Exception $exception) {
            throw new Error($exception->getMessage());
        }
    }

    /**
     * Adds bot layer.
     *
     * @param string $to Directory.
     * @param array $layers Raw layers.
     * @throws Error generic exception.
     */
    private function addBotLayer(string $to, array $layers): void
    {
        // persist
        // runtime version = offset overlay
        if (isset($layers["object"]["version"])) {
            $status = $this->file->put(
                "$to/fusion.bot.php",
                "<?php\n" .
                "// Auto-generated by Fusion package manager.\n" .
                "// Do not modify.\n" .
                "return [\n" .
                "\t\"version\" => \"" . $layers["object"]["version"] . "\"\n" .
                "];"
            );

            if (!$status)
                throw new Error(
                    "Can't create file '$to/fusion.bot.php'."
                );
        }
    }

    /**
     * Returns package root directory.
     *
     * @param string $dir Potential root.
     * @param string $file Archive file.
     * @return string Directory.
     * @throws Error Invalid archive content.
     */
    private function getNormalizedFromDir(string $dir, string $file): string
    {
        // without root directory
        if ($this->file->exists("$dir/fusion.json"))
            return $dir;

        $filenames = $this->dir->getFilenames($dir, SCANDIR_SORT_NONE);

        if ($filenames === false)
            throw new Error(
                "Can't read directory '$dir'."
            );

        // most common prefixed
        foreach ($filenames as $filename)
            if ($filename != "." && $filename != ".." &&
                $this->file->exists("$dir/$filename/fusion.json"))
                return "$dir/$filename";

        // invalid package content
        throw new Error(
            "Can't find the production metadata file " .
            "\"fusion.json\" inside the archive file \"$file\". "
        );
    }

    /**
     * Extends event.
     *
     * @param Event|string $event Event.
     */
    public function extend(Event|string $event): void
    {
        if ($event instanceof Request)
            $event->setPath(
                $this->group->getPath(
                    $this->metas[$event->getId()]->getLayers()

                        // object layer is raw
                        ["object"]["source"]
                )
            );
    }
}