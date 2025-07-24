<?php

declare(strict_types=1);

namespace ChatMessenger\Server;

interface ServerInterface
{
    public function start(): void;
    
    public function stop(): void;
    
    public function isRunning(): bool;
    
    public function getPort(): int;
}