<?php

declare(strict_types=1);

namespace Sillbeam\Core\Console\Style;

use Symfony\Component\Console\Helper\Helper;

/** @internal Treat application/source text as data, never terminal instructions or formatter markup. */
final class TerminalText
{
    public static function clean(string $text): string
    {
        if (1 !== preg_match('//u', $text)) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }
        $text = preg_replace('~\x1B(?:\][^\x07\x1B]*(?:\x07|\x1B\\\\)|\[[0-?]*[ -/]*[@-\x7E]|[ -/]*[@-\x7E])~u', '', $text) ?? '';
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);

        return preg_replace('/[\p{Cc}]|(?:(?!\x{200D})\p{Cf})/u', '', $text) ?? '';
    }

    /** @return list<string> */
    public static function wrap(string $text, int $width): array
    {
        $words = preg_split('/\s+/u', trim(self::clean($text))) ?: [];
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            if (Helper::width($word) > $width) {
                if ('' !== $current) {
                    $lines[] = $current;
                    $current = '';
                }
                preg_match_all('/\X/u', $word, $characters);
                foreach ($characters[0] as $character) {
                    if ('' !== $current && Helper::width($current.$character) > $width) {
                        $lines[] = $current;
                        $current = '';
                    }
                    $current .= $character;
                }
                continue;
            }
            if ('' !== $current && Helper::width($current.' '.$word) > $width) {
                $lines[] = $current;
                $current = '';
            }
            $current .= ('' === $current ? '' : ' ').$word;
        }
        if ('' !== $current) {
            $lines[] = $current;
        }

        return $lines ?: [''];
    }
}
