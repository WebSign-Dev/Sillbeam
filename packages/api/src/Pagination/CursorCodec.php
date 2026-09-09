<?php

declare(strict_types=1);

namespace Nexum\Api\Pagination;

use Nexum\Api\Error\ApiProblemException;

final class CursorCodec
{
    public function encode(string $identifier): string
    {
        return rtrim(strtr(base64_encode(json_encode(['id' => $identifier], \JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }

    public function decode(string $cursor): string
    {
        if ('' === $cursor || \strlen($cursor) > 512 || !preg_match('/^[A-Za-z0-9_-]+$/', $cursor)) {
            throw ApiProblemException::badRequest('The pagination cursor is invalid.', 'invalid_cursor');
        }

        $padding = (4 - \strlen($cursor) % 4) % 4;
        $decoded = base64_decode(strtr($cursor.str_repeat('=', $padding), '-_', '+/'), true);

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
}
