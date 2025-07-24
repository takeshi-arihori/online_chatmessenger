<?php

declare(strict_types=1);

namespace ChatMessenger\Common\Protocol;

interface ProtocolInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function encode(array $data): string;

    /**
     * @return array<string, mixed>
     */
    public function decode(string $data): array;

    public function validate(string $data): bool;

    public function getMaxSize(): int;
}
