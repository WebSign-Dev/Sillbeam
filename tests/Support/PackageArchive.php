<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Support;

/** Test-only inspection of actual ZIP contents; never reads link targets from the checkout. */
final class PackageArchive
{
    private const array ROOT_FILES = ['composer.json', 'README.md', 'LICENSE', 'CHANGELOG.md', 'CONTRIBUTING.md', 'SUPPORT.md', 'SECURITY.md'];
    private const array DOC_DIRECTORIES = ['architecture', 'assets', 'examples', 'guides', 'migration', 'modules', 'operations', 'reference', 'releases'];

    /**
     * Verifies archive boundaries, complete runtime/public docs, byte-identical source and local links.
     *
     * @return array{files: list<string>, uncompressed_bytes: int, source_files: int, markdown_links: int}
     */
    public static function inspect(string $archive, string $repository): array
    {
        $contents = [];
        $bytes = 0;
        foreach (new \RecursiveIteratorIterator(new \PharData($archive)) as $file) {
            $relative = substr($file->getPathname(), \strlen('phar://'.$archive.'/'));
            if (!self::allowed($relative)) {
                throw new \RuntimeException('Unexpected package entry: '.$relative);
            }
            $contents[$relative] = $file->getContent();
            $bytes += $file->getSize();
        }
        $required = [...self::ROOT_FILES, 'docs/README.md'];
        foreach (['src', ...array_map(static fn (string $directory): string => 'docs/'.$directory, self::DOC_DIRECTORIES)] as $directory) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($repository.'/'.$directory, \FilesystemIterator::SKIP_DOTS)) as $file) {
                $relative = substr($file->getPathname(), \strlen($repository) + 1);
                if ($file->isFile() && self::allowed($relative)) {
                    $required[] = $relative;
                }
            }
        }
        $sourceFiles = 0;
        foreach ($required as $relative) {
            if (!\array_key_exists($relative, $contents)) {
                throw new \RuntimeException('Missing required package file: '.$relative);
            }
            if (str_starts_with($relative, 'src/')) {
                ++$sourceFiles;
                if ($contents[$relative] !== file_get_contents($repository.'/'.$relative)) {
                    throw new \RuntimeException('Runtime source differs from checkout: '.$relative);
                }
            }
        }
        $links = 0;
        foreach ($contents as $file => $text) {
            if (!str_ends_with($file, '.md')) {
                continue;
            }
            // Inspect rendered-document links, not literal examples in fenced code blocks.
            $text = preg_replace('/^```[^\n]*\n.*?^```\s*$/ms', '', $text) ?? $text;
            preg_match_all('~\]\(\s*(<[^>]+>|[^\s)]+)(?:\s+"[^"]*")?\s*\)~', $text, $inline);
            preg_match_all('~^\s{0,3}\[[^]]+\]:\s*(<[^>]+>|\S+)~m', $text, $references);
            preg_match_all('~(?:href|src)\s*=\s*["\']([^"\']+)["\']~i', $text, $html);
            foreach (array_merge($inline[1], $references[1], $html[1]) as $target) {
                $target = trim($target, '<>');
                if (str_starts_with($target, '#') || preg_match('~^(?:[a-z][a-z0-9+.-]*:|//)~i', $target)) {
                    continue;
                }
                $path = rawurldecode(explode('?', explode('#', $target, 2)[0], 2)[0]);
                $resolved = self::resolve($file, $path);
                if (null === $resolved || (!isset($contents[$resolved]) && !array_any(array_keys($contents), static fn (string $entry): bool => str_starts_with($entry, rtrim($resolved, '/').'/')))) {
                    throw new \RuntimeException('Broken package link: '.$file.' → '.$target);
                }
                ++$links;
            }
        }

        return ['files' => array_keys($contents), 'uncompressed_bytes' => $bytes, 'source_files' => $sourceFiles, 'markdown_links' => $links];
    }

    private static function allowed(string $path): bool
    {
        if (preg_match('~(?:^|/)\.|(?:^|/)(?:\.env(?:\..*)?|[^/]*\.cache|reference\.php|\.gitkeep)$|\.(?:log|jsonl|tmp|bak)$~', $path)) {
            return false;
        }
        if (\in_array($path, self::ROOT_FILES, true) || 'docs/README.md' === $path || str_starts_with($path, 'src/')) {
            return true;
        }
        foreach (self::DOC_DIRECTORIES as $directory) {
            if (str_starts_with($path, 'docs/'.$directory.'/')) {
                return true;
            }
        }

        return false;
    }

    private static function resolve(string $source, string $target): ?string
    {
        if ('' === $target || str_starts_with($target, '/')) {
            return null;
        }
        $parts = [];
        foreach (explode('/', \dirname($source).'/'.$target) as $part) {
            if ('' === $part || '.' === $part) {
                continue;
            }
            if ('..' === $part) {
                if ([] === $parts) {
                    return null;
                }
                array_pop($parts);
            } else {
                $parts[] = $part;
            }
        }

        return implode('/', $parts);
    }
}
