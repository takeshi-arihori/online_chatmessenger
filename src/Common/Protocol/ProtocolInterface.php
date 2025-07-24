<?php

declare(strict_types=1);

namespace ChatMessenger\Common\Protocol;

interface ProtocolInterface
{
    public function encode(array $data): string;
    
    public function decode(string $data): array;
    
    public function validate(string $data): bool;
    
    public function getMaxSize(): int;
}