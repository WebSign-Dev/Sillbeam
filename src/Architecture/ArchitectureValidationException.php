<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture;

final class ArchitectureValidationException extends \LogicException
{
    public function __construct(public readonly ArchitectureAnalysisResult $result, ?\Throwable $previous = null)
    {
        parent::__construct(implode("\n", array_map(
            static fn (ArchitectureFinding $finding): string => $finding->describe(),
            $result->findings,
        )), 0, $previous);
    }

    /** @param array<string, string|int|null> $context */
    public static function fatal(string $rule, string $message, array $context = [], ?\Throwable $previous = null): self
    {
        return new self(new ArchitectureAnalysisResult([new ArchitectureFinding($rule, $message, $context, 'fatal')], cause: $previous), $previous);
    }
}
