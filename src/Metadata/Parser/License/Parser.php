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

namespace Valvoid\Fusion\Metadata\Parser\License;

use Valvoid\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Metadata as MetadataEvent;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Wrappers\Dir;
use Valvoid\Fusion\Wrappers\File;

/**
 * Parser for inline SPDX license expressions.
 */
class Parser
{
    /** @var string Inline license expression to parse. */
    private string $license;

    /** @var int Length of the license expression. */
    private int $length;

    /** @var array Complete list of SPDX licenses. */
    private array $licenses = [];

    /** @var array Complete list of SPDX exceptions. */
    private array $exceptions = [];

    /**
     * Constructs the parser.
     *
     * @param Box $box Dependency injection container.
     * @param Bus $bus Event bus.
     * @param File $file Wrapper for standard file operations.
     * @param Dir $dir Wrapper for standard directory operations.
     */
    public function __construct(
        private readonly Box $box,
        private readonly Bus $bus,
        private readonly File $file,
        private readonly Dir $dir
    ) {}

    /**
     * Parses license entry.
     *
     * @param string $license Inline license expression to parse.
     */
    public function parse(string $license): void
    {
        $this->license = $license;
        $this->length = strlen($license);
        $i = 0;

        // tokenize for easier validation
        $tokens = $this->getTokens($i, false);

        // load resources once
        if (empty($this->licenses)) {
            $root = $this->dir->getDirname(__DIR__,

                // own root
                4);

            $this->licenses = $this->loadResource($root, "licenses");
            $this->exceptions = $this->loadResource($root, "exceptions");
        }

        // validate tokenized expression
        $this->validateTokens($tokens);
    }

    /**
     * Tokenizes inline SPDX license expression.
     *
     * @param int $i Current parser position.
     * @param bool $group Whether the tokenizer is inside a parenthesized group.
     * @return array Tokenized license expression.
     */
    private function getTokens(int &$i, bool $group): array
    {
        $tokens = [];
        $token = "";

        while ($i < $this->length) {
            $char = $this->license[$i];

            // open group
            if ($char === '(') {
                if ($token !== "") {
                    $tokens[] = $token;
                    $token = "";
                }

                ++$i;

                // groups are represented as nested token arrays
                $tokens[] = $this->getTokens($i, true);

            // close group
            } elseif ($char === ')') {
                if ($token !== "")
                    $tokens[] = $token;

                if ($group === false)
                    $this->reportError(
                        "Missing opening parenthesis."
                    );

                return $tokens;

            // whitespace separator
            } elseif ($char === ' ') {
                if ($token !== "") {
                    $tokens[] = $token;
                    $token = "";
                }

            // append
            } else $token .= $char;

            ++$i;
        }

        if ($token !== "")
            $tokens[] = $token;

        if ($group)
            $this->reportError(
                "Missing closing parenthesis."
            );

        return $tokens;
    }

    /**
     * Loads SPDX resource data.
     *
     * @param string $root Root dir.
     * @param string $name Resource name.
     * @return array Resource data.
     * @see https://github.com/spdx/license-list-data/tree/main/json
     */
    private function loadResource(string $root, string $name): array
    {
        $json = $this->file->get("$root/resources/spdx/$name.json");

        if ($json === false)
            $this->reportError(
                "Unable to load SPDX $name resources."
            );

        $array = json_decode($json, true);

        if (!isset($array[$name]))
            $this->reportError(
                "Invalid SPDX $name resource data."
            );

        return $array[$name];
    }

    /**
     * Validates the tokenized inline SPDX license expression.
     *
     * @param array $tokens Tokenized license expression.
     */
    private function validateTokens(array $tokens): void
    {
        if (empty($tokens))
            $this->reportError(
                "Empty license group."
            );

        // previous token type
        $type = Syntax::NONE;

        foreach ($tokens as $token) {

            // init, and, or expects license or group
            if ($type === Syntax::AND ||
                $type === Syntax::OR ||
                $type === Syntax::NONE)
                if (is_array($token)) {
                    $this->validateTokens($token);
                    $type = Syntax::GROUP;

                } else {
                    $this->validateLicense($token);
                    $type = Syntax::LICENSE;
                }

            // with expects exception
            elseif ($type === Syntax::WITH) {
                if (is_array($token))
                    $this->reportError(
                        "'WITH' must be followed by a license exception."
                    );

                $this->validateException($token);
                $type = Syntax::EXCEPTION;

            // license expects and, or, with
            } elseif ($type === Syntax::LICENSE)
                $type = match ($token) {
                    "AND" => Syntax::AND,
                    "OR" => Syntax::OR,
                    "WITH" => Syntax::WITH,
                    default => $this->reportError(
                        "License must be followed by 'AND', 'OR', or 'WITH'."
                    )
                };

            // group/exception expects and, or
            elseif ($type === Syntax::GROUP ||
                    $type === Syntax::EXCEPTION)
                $type = match ($token) {
                    "AND" => Syntax::AND,
                    "OR" => Syntax::OR,
                    default => $this->reportError(
                        "Group or exception must be followed by 'AND' or 'OR'."
                    )
                };
        }

        if ($type === Syntax::AND ||
            $type === Syntax::OR ||
            $type === Syntax::WITH)
            $this->reportError(
                "Expression must not end with 'AND', 'OR', or 'WITH'."
            );
    }

    /**
     * Validates SPDX license exception identifier.
     *
     * @param string $exception Exception identifier.
     */
    private function validateException(string $exception): void
    {
        foreach ($this->exceptions as $entry)
            if ($entry["licenseExceptionId"] === $exception)
                return;

        $this->reportError(
            "Unknown SPDX license exception."
        );
    }

    /**
     * Validates SPDX license identifier.
     *
     * @param string $license License identifier.
     */
    private function validateLicense(string $license): void
    {
        foreach ($this->licenses as $entry)
            if ($entry["licenseId"] === $license)
                return;

        // custom / unknown licenses
        if (str_starts_with($license, "LicenseRef-") ||
            preg_match('/^DocumentRef-[^:]+:LicenseRef-.+$/', $license))
            return;

        $this->reportError(
            "Unknown SPDX license."
        );
    }

    /**
     * Reports SPDX license parsing error.
     *
     * @param string $message Error message.
     */
    private function reportError(string $message): void
    {
        $this->bus->broadcast(
            $this->box->get(MetadataEvent::class,
                message: $message,
                level: Level::ERROR,
                breadcrumb: ["license"]
        ));
    }
}