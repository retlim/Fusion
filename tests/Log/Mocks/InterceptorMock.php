<?php

namespace Valvoid\Fusion\Tests\Log\Mocks;

use Valvoid\Fusion\Log\Events\Event;
use Valvoid\Fusion\Log\Events\Interceptor;

class InterceptorMock implements Interceptor {
    public function extend(string|Event $event): void {}
}