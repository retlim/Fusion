<?php
/*
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
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Valvoid\Fusion\Log\Serializers\Files\Text;

use Valvoid\Fusion\Dir\Dir;
use Valvoid\Fusion\Log\Events\Errors\Config;
use Valvoid\Fusion\Log\Events\Errors\Deadlock;
use Valvoid\Fusion\Log\Events\Errors\Environment;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Log\Events\Infos\Error as ErrorInfo;
use Valvoid\Fusion\Log\Events\Errors\Lifecycle;
use Valvoid\Fusion\Log\Events\Errors\Metadata;
use Valvoid\Fusion\Log\Events\Errors\Request;
use Valvoid\Fusion\Log\Events\Event;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Events\Infos\Id;
use Valvoid\Fusion\Log\Events\Infos\Name;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Log\Serializers\Files\File;
use Valvoid\Fusion\Wrappers\File as FileWrapper;

/**
 * Text file serializer.
 */
class Text implements File
{
    /** @var Level  */
    private Level $threshold;

    /** @var string Filename. */
    private string $filename;

    /** @var string Storage dir. */
    private string $storage;

    /**
     * Constructs the text file serializer.
     *
     * @param Dir $directory
     * @param FileWrapper $file
     * @param array $configuration
     */
    public function __construct(
        private readonly Dir $directory,
        private readonly FileWrapper $file,
        array $configuration)
    {
        $this->threshold = $configuration["threshold"];
        $this->filename = $configuration["filename"];

        $this->storage = $this->directory->getLogDir();
    }

    /**
     * @inheritdoc
     * @throws Error
     */
    public function log(Level $level, Event|string $event): void
    {
        if ($level->value > $this->threshold->value)
            return;

        if (is_string($event))
            $content = $this->getMessage($level, $event);

        elseif ($event instanceof Deadlock)
            $content = $this->getDeadlock($level, $event);

        elseif ($event instanceof Environment)
            $content = $this->getEnvironment($level, $event);

        elseif ($event instanceof Metadata)
            $content = $this->getMetadata($level, $event);

        elseif ($event instanceof Error)
            $content = $this->getError($level, $event);

        elseif ($event instanceof ErrorInfo)
            $content = $this->getErrorInfo($level, $event);

        elseif ($event instanceof Request)
            $content = $this->getRequest($level, $event);

        elseif ($event instanceof Lifecycle)
            $content = $this->getLifecycle($level, $event);

        elseif ($event instanceof Content)
            $content = $this->formatContent($level, $event);

        elseif ($event instanceof Config)
            $content = $this->getConfig($level, $event);

        elseif ($event instanceof Id)
            $content = $this->getId($level, $event);

        elseif ($event instanceof Name)
            $content = $this->getName($level, $event);

        // custom unknown
        // generic message fallback
        else
            $content = $this->getMessage($level, "$event");

        $this->directory->createDir($this->storage);

        if (!$this->file->put("$this->storage/$this->filename", $content))
            throw new Error(
                "Can't write to the file \"$this->storage/$this->filename\"."
            );
    }

    /**
     * Returns generic string message.
     *
     * @param Level $level Level.
     * @param string $message Message.
     * @return string
     */
    private function getMessage(Level $level, string $message): string
    {
        return "\n" . date("Y.m.d_H:i:s") . " --------- generic " .
            strtolower($level->name) . ":\n$message";
    }

    /**
     * Formats parsed metadata content event.
     *
     * @param Level $level Level.
     * @param Content $content Content.
     */
    private function formatContent(Level $level, Content $content): string
    {
        $info = "\n" . date("Y.m.d_H:i:s") . " --------- content " .
            strtolower($level->name) . ":" .

            "\n". $content->getId()  . " | " . $content->getVersion();

        // complete and
        // short
        if ($this->threshold == Level::VERBOSE)
            $info .=
                "\nname: " . $content->getName() .
                "\ndescription: " . $content->getDescription() .
                "\nsource: " . $content->getSource() .
                "\ndir: " . $content->getDir();

        return $info;
    }

    /**
     * Returns deadlock event.
     *
     * @param Level $level Level.
     * @param Deadlock $deadlock Event.
     * @return string
     */
    private function getDeadlock(Level $level, Deadlock $deadlock): string
    {
        $content = "\n" . date("Y.m.d_H:i:s") . " --------- deadlock " .
            strtolower($level->name) . ":";

        foreach ($deadlock->getLockedPath() as $entry)
            $content .= "\nin: " . $entry["layer"] .
                "\nat: " . implode(" | ", $entry["breadcrumb"]) .
                "\nas: " . $entry["source"];

        $content .= "\nin: " . $deadlock->getLockedLayer() .
            "\nat: " . implode(" | ", $deadlock->getLockedBreadcrumb()) .
            "\n    ---";

        foreach ($deadlock->getConflictPath() as $entry)
            $content .= "\nin: " . $entry["layer"] .
                "\nat: " . implode(" | ", $entry["breadcrumb"]) .
                "\nas: " . $entry["source"];

        $content .= "\nin: " . $deadlock->getConflictLayer() .
            "\nat: " . implode(" | ", $deadlock->getConflictBreadcrumb()) .
            "\nis: " . $deadlock->getMessage();

        return $content;
    }

    /**
     * Returns environment event.
     *
     * @param Level $level Level.
     * @param Environment $environment Environment.
     * @return string
     */
    private function getEnvironment(Level $level, Environment $environment): string
    {
        $content = "\n" . date("Y.m.d_H:i:s") . " --------- environment " .
            strtolower($level->name) . ":";

        foreach ($environment->getPath() as $entry)
            $content .= "\nin: " . $entry["layer"] .
                "\nat: " . implode(" | ", $entry["breadcrumb"]) .
                "\nas: " . $entry["source"];

        $content .= "\nin: " . $environment->getLayer() .
            "\nat: " . implode(" | ", $environment->getBreadcrumb()) .
            "\nis: " . $environment->getMessage();

        return $content;
    }

    /**
     * Returns lifecycle event.
     *
     * @param Level $level Level.
     * @param Lifecycle $lifecycle Lifecycle.
     */
    private function getLifecycle(Level $level, Lifecycle $lifecycle): string
    {
        $content = "\n" . date("Y.m.d_H:i:s") . " --------- lifecycle " .
            strtolower($level->name) . ":";

        foreach ($lifecycle->getPath() as $entry)
            $content .= "\nin: " . $entry["layer"] .
                "\nat: " . implode(" | ", $entry["breadcrumb"]) .
                "\nas: " . $entry["source"];

        $content .=  "\nin: " . $lifecycle->getLayer() .
            "\nat: " . implode(" | ", $lifecycle->getBreadcrumb()) .
            "\nis: " . $lifecycle->getMessage();

        return $content;
    }

    /**
     * Returns metadata event.
     *
     * @param Level $level Level.
     * @param Metadata $metadata Metadata.
     * @return string
     */
    private function getMetadata(Level $level, Metadata $metadata): string
    {
        $content = "\n" . date("Y.m.d_H:i:s") . " --------- metadata " .
            strtolower($level->name) . ":";

        foreach ($metadata->getPath() as $item)
            $content .= "\nin: " . $item["layer"] .
                "\nat: " . implode(" | ", $item["breadcrumb"]) .
                "\nas: " . $item["source"];

        $content .= "\nin: " . $metadata->getLayer();

        $index = $metadata->getBreadcrumb();

        // meta maybe is empty
        // no index
        if ($index)
            $content .= "\nat: " . implode(" | ", $index);

        $content .= "\nis: " . $metadata->getMessage();

        return $content;
    }

    /**
     * Returns error event.
     *
     * @param Level $level Level.
     * @param Error $error Error.
     * @return string
     */
    private function getError(Level $level, Error $error): string
    {
        $content = "\n" . date("Y.m.d_H:i:s") . " --------- error " .
            strtolower($level->name) . ":";

        foreach (array_reverse($error->getTrace()) as $entry)
            $content .= "\nin: " . $entry["line"] . " - " . $entry["file"] .
                "\nat: " . ($entry["class"] ?? "") . ($entry["type"] ?? "") . $entry["function"] . "()" ;

        $content .= "\nin: " . $error->getLine() . " - " . $error->getFile() .
            "\nis: " . $error->getMessage();

        return $content;
    }

    /**
     * Returns error event.
     *
     * @param Level $level Level.
     * @param ErrorInfo $info Error.
     * @return string
     */
    private function getErrorInfo(Level $level, ErrorInfo $info): string
    {
        $content = "\n" . date("Y.m.d_H:i:s") . " --------- error info " .
            strtolower($level->name) . ":";

        foreach (array_reverse($info->getPath()) as $entry)
            $content .= "\nin: " . $entry["line"] . " - " . $entry["file"] .
                "\nat: " . ($entry["class"] ?? "") . ($entry["type"] ?? "") . $entry["function"] . "()" ;

        $content .= "\nis: " . $info->getMessage() . " | code: " . $info->getCode();

        return $content;
    }

    /**
     * Returns request event.
     *
     * @param Level $level Level.
     * @param Request $request Request.
     * @return string
     */
    private function getRequest(Level $level, Request $request): string
    {
        $content = "\n" . date("Y.m.d_H:i:s") . " --------- request " .
            strtolower($level->name) . ":";

        foreach ($request->getPath() as $entry)
            $content .= "\nin: " . $entry["layer"] .
                "\nat: " . implode(" | ", $entry["breadcrumb"]) .
                "\nas: " . $entry["source"];

        foreach ($request->getSources() as $source)
            $content .= "\nby: $source";

        $content .= "\nis: " . $request->getMessage();

        return $content;
    }

    /**
     * Returns config event.
     *
     * @param Level $level Level.
     * @param Config $config Config.
     * @return string
     */
    private function getConfig(Level $level, Config $config): string
    {
        $content = "\n" . date("Y.m.d_H:i:s") . " --------- config " .
            strtolower($level->name) . ":";

        $content .= "\nin: " . $config->getLayer() .
            "\nat: " . implode(" | ", $config->getBreadcrumb()) .
            "\nis: " . $config->getMessage();

        return $content;
    }


    /**
     * Returns ID event.
     *
     * @param Level $level Level.
     * @param Id $id ID.
     */
    private function getId(Level $level, Id $id): string
    {
        $content = "\n" . date("Y.m.d_H:i:s") . " --------- id " .
            strtolower($level->name) . ":";

        $content .= "\nid: " . $id->getId();

        return $content;
    }

    /**
     * Returns name event.
     *
     * @param Level $level Level.
     * @param Name $name Name.
     */
    private function getName(Level $level, Name $name): string
    {
        $content = "\n" . date("Y.m.d_H:i:s") . " --------- name " .
            strtolower($level->name) . ":";

        $content .= "\nname: " . $name->getName();

        return $content;
    }
}