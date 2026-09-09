<?php

declare(strict_types=1);

namespace Nexum\Api\Pagination;

use Nexum\Api\Error\ApiProblemException;

final readonly class CursorCodec
{
    public function __construct(#[\SensitiveParameter] private string $signingKey)
    {
        if (\strlen($signingKey) < 16) {
            throw new \InvalidArgumentException('The kernel secret must contain at least 16 characters to sign API cursors.');
        }
    }

    public function encode(string $identifier): string
    {
        $payload = $this->base64(json_encode(['id' => $identifier], \JSON_THROW_ON_ERROR));

        return $payload.'.'.$this->base64(hash_hmac('sha256', $payload, $this->signingKey, true));
    }

    public function decode(string $cursor): string
    {
        if ('' === $cursor || \strlen($cursor) > 512 || 1 !== preg_match('/^([A-Za-z0-9_-]+)\.([A-Za-z0-9_-]+)$/', $cursor, $parts)) {
            throw ApiProblemException::badRequest('The pagination cursor is invalid.', 'invalid_cursor');
        }

        $expected = $this->base64(hash_hmac('sha256', $parts[1], $this->signingKey, true));
        if (!hash_equals($expected, $parts[2])) {
            throw ApiProblemException::badRequest('The pagination cursor is invalid.', 'invalid_cursor');
        }
        $decoded = $this->decodeBase64($parts[1]);

        if (false === $decoded) {
            throw ApiProblemException::badRequest('The pagination cursor is invalid.', 'invalid_cursor');
        }

        try {
            $data = json_decode($decoded, true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ApiProblemException::badRequest('The pagination cursor is invalid.', 'invalid_cursor');
        }

        if (!\is_array($data) || !\is_string($data['id'] ?? null) || '' === $data['id']) {
            throw ApiProblemException::badRequest('The pagination cursor is invalid.', 'invalid_cursor');
        }

        return $data['id'];
    }

    private function base64(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function decodeBase64(string $value): string|false
    {
        $padding = (4 - \strlen($value) % 4) % 4;

        return base64_decode(strtr($value.str_repeat('=', $padding), '-_', '+/'), true);
    }
}
