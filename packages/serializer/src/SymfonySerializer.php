<?php

declare(strict_types=1);

namespace Nexum\Serializer;

use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;

final readonly class SymfonySerializer implements SerializerInterface
{
    public function __construct(private SymfonySerializerInterface $serializer)
    {
    }

    public function json(mixed $value, array $context = []): string
    {
        return $this->serializer->serialize($value, 'json', $this->context($context));
    }

    public function fromJson(string $json, string $type, array $context = []): mixed
    {
        if (\strlen($json) > 1_048_576) {
            throw new \InvalidArgumentException('The JSON payload exceeds the 1 MiB serializer limit.');
        }

        return $this->serializer->deserialize($json, $type, 'json', $this->context($context));
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function context(array $context): array
    {
        return $context + [AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => static fn (object $object): string => throw new \UnexpectedValueException(\sprintf('Circular reference involving %s.', $object::class))];
    }
}
