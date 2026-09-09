<?php

declare(strict_types=1);

namespace Nexum\Serializer;

interface SerializerInterface
{
    /** @param array<string, mixed> $context */
    public function json(mixed $value, array $context = []): string;

    /** @param array<string, mixed> $context */
    public function fromJson(string $json, string $type, array $context = []): mixed;
}
