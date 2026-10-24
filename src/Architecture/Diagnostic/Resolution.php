<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture\Diagnostic;

/** @internal A proposed resolution, never an automatic edit or runtime instruction. */
final readonly class Resolution
{
    /** Technical steps require evidence; domain options explicitly require a human decision.
     * @param 'technical'|'option' $kind
     * @param list<string>         $steps
     * @param list<string>         $files         evidence/target locations, never guessed
     * @param list<string>         $configuration known logical configuration addresses, not file paths
     */
    public function __construct(public string $kind, public array $steps, public array $files = [], public array $configuration = [])
    {
        if (!\in_array($kind, ['technical', 'option'], true) || [] === $steps) {
            throw new \InvalidArgumentException('A resolution needs a technical/option kind, nonempty steps.');
        }
    }
}
