<?php
/**
 * Fusion. A package manager for PHP-based projects.
 * Copyright Valvoid
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
$lazy = require "$root/cache/loadable/lazy.php";
$classnames = array_keys($lazy);
$lazy += require "$root/cache/loadable/tests/lazy.php";
$result = 0;
$coverageClassnames = [];

spl_autoload_register(function (string $loadable) use ($root, $lazy)
{
    require $root . $lazy[$loadable];
});

/** @var Test[] $tests */
$tests = [
    new Valvoid\Fusion\Tests\Bus\BusTest,
    new Valvoid\Fusion\Tests\Container\ContainerTest,
    new Valvoid\Fusion\Tests\Config\ConfigTest,
    new Valvoid\Fusion\Tests\Config\Parser\DirTest,
    new Valvoid\Fusion\Tests\Config\Parser\Hub\HubTest,
    new Valvoid\Fusion\Tests\Config\Parser\Log\LogTest,
    new Valvoid\Fusion\Tests\Config\Parser\ParserTest,
    new Valvoid\Fusion\Tests\Config\Parser\Tasks\TasksTest,
    new Valvoid\Fusion\Tests\Config\Interpreter\InterpreterTest,
    new Valvoid\Fusion\Tests\Config\Interpreter\DirTest,
    new Valvoid\Fusion\Tests\Config\Interpreter\PersistenceTest,
    new Valvoid\Fusion\Tests\Config\Interpreter\HubTest,
    new Valvoid\Fusion\Tests\Config\Interpreter\LogTest,
    new Valvoid\Fusion\Tests\Config\Interpreter\TasksTest,
    new Valvoid\Fusion\Tests\Dir\DirTest,
    new Valvoid\Fusion\Tests\Hub\HubTest,
    new Valvoid\Fusion\Tests\Metadata\Interpreter\EnvironmentTest,
    new Valvoid\Fusion\Tests\Metadata\Interpreter\InterpreterTest,
    new Valvoid\Fusion\Tests\Metadata\Interpreter\LifecycleTest,
    new Valvoid\Fusion\Tests\Metadata\Interpreter\StructureTest,
    new Valvoid\Fusion\Tests\Hub\Cache\CacheTest,
    new Valvoid\Fusion\Tests\Log\LogTest,
    new Valvoid\Fusion\Tests\Util\Version\InterpreterTest,
    new Valvoid\Fusion\Tests\Util\Pattern\InterpreterTest,
    new Valvoid\Fusion\Tests\Tasks\Build\Config\InterpreterTest,
    new Valvoid\Fusion\Tests\Tasks\Build\Config\NormalizerTest,
    new Valvoid\Fusion\Tests\Tasks\Build\Config\ParserTest,
    new Valvoid\Fusion\Tests\Tasks\Build\SAT\GraphTest,
    new Valvoid\Fusion\Tests\Tasks\Build\SAT\ClauseTest,
    new Valvoid\Fusion\Tests\Tasks\Build\SAT\SolverTest,
    new Valvoid\Fusion\Tests\Tasks\Build\BuildTest,
    new Valvoid\Fusion\Tests\Tasks\Copy\CopyTest,
    new Valvoid\Fusion\Tests\Tasks\Copy\Config\InterpreterTest,
    new Valvoid\Fusion\Tests\Tasks\Categorize\CategorizeTest,
    new Valvoid\Fusion\Tests\Tasks\Categorize\Config\InterpreterTest,
    new Valvoid\Fusion\Tests\Tasks\Download\DownloadTest,
    new Valvoid\Fusion\Tests\Tasks\Download\Config\InterpreterTest,
    new Valvoid\Fusion\Tests\Tasks\Extend\ExtendTest,
    new Valvoid\Fusion\Tests\Tasks\Extend\Config\InterpreterTest,
    new Valvoid\Fusion\Tests\Tasks\Image\ImageTest,
    new Valvoid\Fusion\Tests\Tasks\Image\Config\InterpreterTest,
    new Valvoid\Fusion\Tests\Tasks\Inflate\InflateTest,
    new Valvoid\Fusion\Tests\Tasks\Inflate\Config\InterpreterTest,
    new Valvoid\Fusion\Tests\Tasks\Register\RegisterTest,
    new Valvoid\Fusion\Tests\Tasks\Register\Config\InterpreterTest,
    new Valvoid\Fusion\Tests\Tasks\Replicate\ReplicateTest,
    new Valvoid\Fusion\Tests\Tasks\Replicate\Config\ParserTest,
    new Valvoid\Fusion\Tests\Tasks\Replicate\Config\InterpreterTest,
    new Valvoid\Fusion\Tests\Tasks\Replicate\Config\NormalizerTest,
    new Valvoid\Fusion\Tests\Tasks\Shift\Config\InterpreterTest,
    new Valvoid\Fusion\Tests\Tasks\Shift\ShiftTest,
    new Valvoid\Fusion\Tests\Tasks\Snap\Config\InterpreterTest,
    new Valvoid\Fusion\Tests\Tasks\Snap\SnapTest,
    new Valvoid\Fusion\Tests\Tasks\Stack\Config\InterpreterTest,
    new Valvoid\Fusion\Tests\Tasks\Stack\StackTest,
    new Valvoid\Fusion\Tests\Group\GroupTest
];

foreach ($tests as $test) {
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

try {

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