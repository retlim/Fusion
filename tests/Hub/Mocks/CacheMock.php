<?php

namespace Valvoid\Fusion\Tests\Hub\Mocks;

use Valvoid\Fusion\Hub\APIs\Local\Local;
use Valvoid\Fusion\Hub\APIs\Remote\Remote;
use Valvoid\Fusion\Hub\Cache;

class CacheMock extends Cache
{
    public string $root;

    public function __construct(string $root = "")
    {
        $this->root = $root;
    }

    public function getLocalDir(array $source): string
    {
        return $this->root;
    }

    public function getReferencesState(array $source): bool|int
    {
        return true;
    }

    public function getFileState(array $source, string $filename, Local|Remote $api): bool|int
    {
        return true;
    }

    public function getVersions(string $api, string $path, array $reference): array
    {
        return [
            "1.3.4",
            "1.2.3"
        ];
    }
}