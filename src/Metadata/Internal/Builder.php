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

namespace Valvoid\Fusion\Metadata\Internal;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Metadata as MetadataEvent;
use Valvoid\Fusion\Log\Events\Errors\Metadata as MetadataError;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Metadata\Interpreter\Interpreter;
use Valvoid\Fusion\Metadata\Normalizer\Normalizer;
use Valvoid\Fusion\Metadata\Normalizer\Structure;
use Valvoid\Fusion\Metadata\Parser\Parser;

/**
 * Internal metadata builder.
 */
class Builder
{
    /** @var array Merged data. */
    private array $content = [];

    /** @var string Current layer. */
    private string $layer = "object";

    /** @var array Layers. */
    private array $layers;

    /**
     * Constructs the builder.
     *
     * @param Box $box Dependency injection container.
     * @param Bus $bus Event bus.
     * @param string $dir Recursive or nested directory (to).
     * @param string $source Internal inline source (from).
     */
    public function __construct(
        private readonly Box $box,
        private readonly Bus $bus,
        string $dir,
        string $source)
    {
        // reverse overlay order
        // object - required
        // fusion.bot.php
        // fusion.local.php
        // fusion.dev.php
        // fusion.json - required
        $this->layers = [
            "production" => null,
            "development" => null,
            "local" => null,
            "bot" => null,

            // no intersection with other layers
            "object" => [
                "content" => [

                    // static normalized
                    // raw == parsed
                    "raw" => [
                        "dir" => $dir,
                        "source" => $source
                    ],
                    "parsed" => [
                        "dir" => $dir,
                        "source" => $source
                    ]
                ]
            ]
        ];
    }

    /**
     * Adds bot layer.
     *
     * @param string $file File.
     * @param array $content Content.
     */
    public function addBotLayer(array $content, string $file): void
    {
        $this->addLayer("bot", $content, $file);
    }

    /**
     * Adds local layer.
     *
     * @param string $file File.
     * @param array $content Content.
     */
    public function addLocalLayer(array $content, string $file): void
    {
        $this->addLayer("local", $content, $file);
    }

    /**
     * Adds development layer.
     *
     * @param string $file File.
     * @param array $content Content.
     */
    public function addDevelopmentLayer(array $content, string $file): void
    {
        $this->addLayer("development", $content, $file);
    }

    /**
     * Returns internal metadata.
     *
     * @return Internal Metadata.
     */
    public function getMetadata(): Internal
    {
        $this->content = [];

        $this->normalize();
        $this->normalizeIndividually("production");
        $this->normalizeIndividually("development");
        $this->normalizeIndividually("local");

        // required layer
        $this->content["dependencies"]["production"] =
            $this->layers["production"]["content"]["parsed"]["dependencies"];

        $this->bus->addReceiver(self::class, $this->handleBusEvent(...),
            MetadataEvent::class);

        // optional dev
        if (isset($this->layers["development"])) {
            $this->layer = "development";
            $this->content["dependencies"]["development"] =
                $this->layers["development"]["content"]["parsed"]["dependencies"];

            $intersection = array_intersect(
                $this->content["dependencies"]["production"],
                $this->content["dependencies"]["development"]
            );

            if ($intersection)
                $this->bus->broadcast(
                    $this->box->get(MetadataEvent::class,
                        message: "Nested source intersection: " .
                        implode(', ', $intersection),
                        level: Level::ERROR,
                        breadcrumb: ["structure"]
                    ));

        } else
            $this->content["dependencies"]["development"] = null;

        // optional local
        if (isset($this->layers["local"])) {
            $this->layer = "local";
            $this->content["dependencies"]["local"] =
                $this->layers["local"]["content"]["parsed"]["dependencies"];

            $intersection = array_intersect(
                $this->content["dependencies"]["production"],
                $this->content["dependencies"]["local"]
            );

            if ($intersection)
                $this->bus->broadcast(
                    $this->box->get(MetadataEvent::class,
                        message: "Nested source intersection: " .
                        implode(', ', $intersection),
                        level: Level::ERROR,
                        breadcrumb: ["structure"]
                    ));

            if ($this->content["dependencies"]["development"]) {
                $intersection = array_intersect(
                    $this->content["dependencies"]["development"],
                    $this->content["dependencies"]["local"]
                );

                if ($intersection)
                    $this->bus->broadcast(
                        $this->box->get(MetadataEvent::class,
                            message: "Nested source intersection: " .
                            implode(', ', $intersection),
                            level: Level::ERROR,
                            breadcrumb: ["structure"]
                        ));
            }

        } else
            $this->content["dependencies"]["local"] = null;

        $this->bus->removeReceiver(self::class);

        return $this->box->get(Internal::class,
            layers: $this->getRawLayers(),
            content: $this->content
        );
    }

    /**
     * Adds production layer.
     *
     * @param string $file File.
     * @param string $content Content.
     * @throws MetadataError Invalid metadata exception.
     */
    public function addProductionLayer(string $content, string $file): void
    {
        $content = json_decode($content, true);

        if ($content === null)
            throw $this->box->get(MetadataError::class,

                // identifier
                source: $this->layers["object"]["content"]["raw"]["source"],
                message: "Can't decode JSON content. " .
                json_last_error_msg(),
                layer: $file
            );

        $this->addLayer("production", $content, $file);
    }

    /**
     * Normalizes individually layer.
     *
     * @param string $layer Layer.
     */
    private function normalizeIndividually(string $layer): void
    {
        // optional development
        // validate and extract ids
        if (!isset($this->layers[$layer]["content"]["parsed"]["structure"]))
            return;

        $this->bus->addReceiver(self::class, $this->handleBusEvent(...),
            MetadataEvent::class);

        $this->layer = $layer;
        $content = $this->layers[$layer]["content"]["parsed"];

        $this->box->get(Structure::class,
            layer: $layer)
            ->normalize($content);

        // extract dependencies
        foreach ($content["structure"]["sources"] as $dir => $sources)
            if ($dir)
                $this->setDependencies($sources);

        $this->bus->removeReceiver(self::class);
    }

    /**
     * Sets dependencies.
     *
     * @param array $sources Sources.
     */
    private function setDependencies(array $sources): void
    {
        $dependencies = &$this->layers[$this->layer]["content"]["parsed"]["dependencies"];

        foreach ($sources as $source) {
            $source = explode('/', $source);

            // remove api and reference
            array_shift($source);
            array_pop($source);

            // remove ' parts
            foreach ($source as $i => $segment)
                if ($segment[0] === "'")
                    unset($source[$i]);

            $dependencies[] = implode('/', $source);
        }
    }

    /**
     * Adds layer.
     *
     * @param string $layer Layer.
     * @param string $file File.
     * @param array $content Content.
     */
    private function addLayer(string $layer, array $content, string $file = ""): void
    {
        $this->content = [];
        $this->layer = $layer;
        $this->layers[$layer] = [
            "file" => $file,
            "content" => [
                "raw" => $content
            ]
        ];

        // bus wrapper
        // error handling
        $this->bus->addReceiver(self::class, $this->handleBusEvent(...),
            MetadataEvent::class);

        $this->box->get(Interpreter::class)
            ->interpret($layer, $content);

        $this->box->get(Parser::class)
            ->parse($content);

        $this->bus->removeReceiver(self::class);

        $this->layers[$layer]["content"]["parsed"] = [
            "dependencies" => [],
            ...$content
        ];
    }

    /**
     * Normalizes metadata.
     */
    private function normalize(): void
    {
        $this->layer = "all";
        $this->content = [];

        $this->bus->addReceiver(self::class, $this->handleBusEvent(...),
            MetadataEvent::class);

        // overlay existing
        foreach ($this->layers as $layer)
            if ($layer)
                $this->box->get(Normalizer::class)
                    ->overlay($this->content, $layer["content"]["parsed"]);

        unset($this->content["dependencies"]);

        $this->box->get(Normalizer::class)
            ->normalize($this->content);

        $this->bus->removeReceiver(self::class);
    }

    /**
     * Returns raw layers.
     *
     * @return array Layers.
     */
    private function getRawLayers(): array
    {
        $layers = [];

        foreach ($this->layers as $category => $layer)
            if ($layer)
                $layers[$layer["file"] ?? $category] = $layer["content"]["raw"];

        return $layers;
    }

    /**
     * Handles bus event.
     *
     * @param MetadataEvent $event Root event.
     * @throws MetadataError Invalid metadata exception.
     */
    protected function handleBusEvent(MetadataEvent $event): void
    {
        $breadcrumb = $event->getBreadcrumb();
        $abstract = $event->getAbstract();
        $layer = "unknown layer";
        $row = 0;

        switch ($this->layer) {
            case "object":
                $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

                // reverse
                // take first match
                foreach (array_reverse($backtrace) as $entry)
                    if ($entry["class"] == self::class) {
                        $layer = $entry["file"];
                        $row = $entry["line"];
                        break;
                    }

                break;

            case "production":
            case "bot":
            case "local":
            case "development":
                $layer = $this->layers[$this->layer]["file"];
                break;

            // all
            default:
                $layer = $this->layers["production"]["file"];
        }

        $metadata = $this->box->get(MetadataError::class,

            // identifier
            source: $this->layers["object"]["content"]["raw"]["source"],
            message: $event->getMessage(),
            layer: $layer,
            breadcrumb: $breadcrumb,
            row: $row
        );

        match ($event->getLevel()) {
            Level::ERROR => throw $metadata,
            Level::WARNING => $this->box->get(Log::class)->warning($metadata),
            Level::NOTICE => $this->box->get(Log::class)->notice($metadata),
            Level::VERBOSE => $this->box->get(Log::class)->verbose($metadata),
            Level::INFO => $this->box->get(Log::class)->info($metadata)
        };
    }
}