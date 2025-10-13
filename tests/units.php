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

use Valvoid\Fusion\Tests\Test;

$root = dirname(__DIR__);
$lazy = require "$root/state/loadable/lazy.php";
$classnames = [];

foreach ($lazy as $class => $path) {
    if (str_starts_with($class, "Valvoid\Fusion\Test"))
        continue;

    $classnames[] = $class;
}

$result = 0;
$coverageClassnames = [];

spl_autoload_register(function (string $loadable) use ($root, $lazy)
{
    require $root . $lazy[$loadable];
});

try {
    foreach ($lazy as $classname => $dir) {
        if (!str_ends_with($classname, "Test"))
            continue;

        $reflection = new ReflectionClass($classname);

        if ($reflection->isSubclassOf(Test::class)) {
            $test = $reflection->newInstance();

            if (!$test->getResult())
                $result = 1;

            $coverage = $test->getCoverage();

            // array, string, null
            if (is_array($coverage)) {
                foreach ($coverage as $coverageItem)
                    if (!in_array($coverageItem, $coverageClassnames) &&
                        in_array($coverageItem, $classnames))
                        $coverageClassnames[] = $coverageItem;

            } elseif (is_string($coverage) &&
                !in_array($coverage, $coverageClassnames) &&
                in_array($coverage, $classnames))
                $coverageClassnames[] = $coverage;
        }
    }

    // simple coverage by classname
    foreach ($classnames as $i => $classname) {
        $reflection = new ReflectionClass($classname);

        if ($reflection->isAbstract() || $reflection->isEnum() ||
            $reflection->isTrait() || $reflection->isInterface())
            unset($classnames[$i]);
    }

    echo "\nCode coverage: " .
        round(100 * sizeof($coverageClassnames) / sizeof($classnames), 2) .
        "%";

} catch (ReflectionException $e) {
    echo $e->getMessage();
}

// strict
// zero tolerance
echo "\n";
exit($result);