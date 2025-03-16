<?php

namespace Valvoid\Fusion\Tests\Hub\Mocks;

use Valvoid\Fusion\Hub\Cache;

class CacheMock extends Cache
{
    public string $root;

    public function __construct(string $root = "")
    {
        $this->root = $root;
    }
}