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

namespace Valvoid\Fusion\Metadata\External;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Bus\Proxy as Bus;
use Valvoid\Fusion\Bus\Events\Metadata as MetadataEvent;
use Valvoid\Fusion\Log\Events\Errors\Metadata as MetadataError;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Log\Proxy as Log;
use Valvoid\Fusion\Metadata\External\Normalizer\Reference;
use Valvoid\Fusion\Metadata\External\Parser\Source;
use Valvoid\Fusion\Metadata\Interpreter\Interpreter;
use Valvoid\Fusion\Metadata\Normalizer\Normalizer;
use Valvoid\Fusion\Metadata\Normalizer\Structure;
use Valvoid\Fusion\Metadata\Parser\Parser;

/**
 * External metadata builder.
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
     * @param string $source External inline source (from).
     */
    public function __construct(
        private readonly Box $box,
        private readonly Bus $bus,
        string $dir,
        string $source)
    {
        // reverse overlay order
        // object - required
        // fusion.json - required
        $this->layers = [
            "production" => null,

            // no intersection with other layers
            "object" => [
                "content" => [
                    "raw" => [

                        // origin pattern reference
                        "source" => $source,
                        "dir" => $dir
                    ]
                ]
            ]
        ];

        // bus wrapper
        // error handling
        $this->bus->addReceiver(self::class, $this->handleBusEvent(...),
            MetadataEvent::class);

        $parser = $this->box->get(Source::class,
            source: $source);

        // mutable
        // pattern - normalized reference
        $this->layers["object"]["content"]["parsed"] = [
            "id" => $parser->getId(),
            "source" => $parser->getSource(),
            "dir" => $dir
        ];

        $this->bus->removeReceiver(self::class);
    }

    /**
     * Returns package ID.
     *
     * @return string ID.
     */
    public function getId(): string
    {
        return $this->layers["object"]["content"]["parsed"]["id"];
    }

    /**
     * Returns raw dir.
     *
     * @return string Dir.
     */
    public function getRawDir(): string
    {
        return $this->layers["object"]["content"]["raw"]["dir"];
    }

    /**
     * Adds lazy source pointer.
     *
     * @param string $reference Semantic version with optional offset.
     */
    public function normalizeReference(string $reference): void
    {
        $this->layer = "object";
        $reference = $this->box->get(Reference::class)
            ->getNormalizedReference($reference);

        // fake version
        // branch|commit offset
        if (isset($reference["version"])) {
            $this->layers["object"]["content"]["parsed"]["version"] = $reference["version"];

        // reset
        } else
            unset($this->layers["object"]["content"]["parsed"]["version"]);

        // progressive
        // replace pattern
        $this->layers["object"]["content"]["parsed"]["source"]["reference"] =

            // absolute pointer
            // extracted by pattern reference
            $reference["reference"];
    }

    /**
     * Returns parsed source.
     *
     * @return array{
     *     api: string,
     *     path: string,
     *     reference: array,
     *     prefix: string
     * } Source.
     */
    public function getParsedSource(): array
    {
        return $this->layers["object"]["content"]["parsed"]["source"];
    }

    /**
     * Returns normalized source.
     *
     * @return array{
     *     api: string,
     *     path: string,
     *     reference: string,
     *     prefix: string
     * } Source.
     */
    public function getNormalizedSource(): array
    {
        return $this->layers["object"]["content"]["parsed"]["source"];
    }

    /**
     * Returns external metadata.
     *
     * @return External Metadata.
     */
    public function getMetadata(): External
    {
        $this->normalize();
        $this->normalizeIndividually("production");

        $this->content["dependencies"]["production"] =
            $this->layers["production"]["content"]["parsed"]["dependencies"];

        return $this->box->get(External::class,
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

        $this->box->get(Parser::class)->
            parse($content);

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