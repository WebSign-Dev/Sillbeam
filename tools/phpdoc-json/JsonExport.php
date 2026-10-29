<?php

declare(strict_types=1);

namespace Sillbeam\Documentation;

/**
 * Development-only projection of phpDocumentor's Twig output into the public JSON contract.
 *
 * @internal
 */
final class JsonExport
{
    /**
     * @param array<string, mixed> $data intermediate phpDocumentor data, including retained source and byte spans
     *
     * @throws \RuntimeException if required source/span evidence is missing or identities are duplicated
     * @throws \JsonException    if documentation cannot be encoded losslessly as UTF-8 JSON
     */
    public function encode(array $data, string $package, string $version): string
    {
        if (!isset($data['elements'], $data['files']) || !\is_array($data['elements']) || !\is_array($data['files'])) {
            throw new \RuntimeException('Missing phpDocumentor descriptor collections.');
        }
        $elements = [];
        foreach ($data['elements'] as $element) {
            $id = $element['id'];
            if (!\is_string($id) || '' === $id || isset($elements[$id])) {
                throw new \RuntimeException('Missing or duplicate phpDocumentor element identity.');
            }
            if (\in_array($element['kind'], ['method', 'function'], true)) {
                $span = $element['_source'];
                $source = $data['files'][$span['file']] ?? null;
                if (!\is_string($source) || !\is_int($span['start']) || !\is_int($span['end']) || $span['start'] < 0 || $span['end'] < $span['start'] || $span['end'] >= \strlen($source)) {
                    throw new \RuntimeException('Missing original declaration source/span for '.$id.'. Rebuild the phpDocumentor export cache.');
                }
                $element['signature'] = $this->signature(substr($source, $span['start'], $span['end'] - $span['start'] + 1));
            }
            unset($element['_source']);
            $elements[$id] = $element;
        }
        ksort($elements, \SORT_STRING);

        return json_encode([
            'schema_version' => 1,
            'package' => $package,
            'version' => $version,
            'language' => 'en',
            'elements' => array_values($elements),
        ], \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE)."\n";
    }

    /**
     * Keeps original bytes from a known phpDocumentor declaration span. Tokenization
     * only finds the body delimiter; it does not discover or analyze PHP declarations.
     */
    private function signature(string $declaration): string
    {
        $tokens = token_get_all('<?php '.$declaration);
        array_shift($tokens);
        $text = '';
        $parentheses = 0;
        foreach ($tokens as $token) {
            if (\is_string($token)) {
                if ('{' === $token && 0 === $parentheses) {
                    return rtrim($text);
                }
                if ('(' === $token) {
                    ++$parentheses;
                } elseif (')' === $token) {
                    --$parentheses;
                }
            }
            $text .= \is_array($token) ? $token[1] : $token;
        }

        return $text;
    }
}
