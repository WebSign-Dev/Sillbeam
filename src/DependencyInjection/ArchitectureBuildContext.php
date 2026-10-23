<?php

declare(strict_types=1);

namespace Sillbeam\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\KernelInterface;

/** Build-only policy; warm request paths never instantiate this helper. */
final class ArchitectureBuildContext
{
    public static function assertCompilationAllowed(ContainerBuilder $container): void
    {
        if ($container->hasParameter('kernel.debug') && false === $container->getParameter('kernel.debug') && self::isHttp()) {
            // Do not include source paths, findings or deployment instructions in this error.
            throw new ServiceUnavailableHttpException(null, 'Service temporarily unavailable.');
        }
    }

    public static function isHttp(): bool
    {
        if (!\in_array(\PHP_SAPI, ['cli', 'phpdbg'], true)) {
            return true;
        }
        $console = false;
        // CLI-based workers can call handle() without HTTP superglobals. A surrounding
        // HTTP request always wins over a nested command; explicit CLI builds win over
        // inherited worker environment variables.
        foreach (debug_backtrace(\DEBUG_BACKTRACE_PROVIDE_OBJECT | \DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
            $object = $frame['object'] ?? null;
            if ('handle' === $frame['function'] && $object instanceof KernelInterface) {
                return true;
            }
            $console = $console || $object instanceof \Symfony\Component\Console\Application || $object instanceof \Symfony\Component\Console\Command\Command;
        }
        if ($console) {
            return false;
        }
        if (isset($_SERVER['REQUEST_METHOD'])) {
            return true;
        }
        $mode = $_SERVER['APP_RUNTIME_MODE'] ?? $_ENV['APP_RUNTIME_MODE'] ?? getenv('APP_RUNTIME_MODE');
        if (\is_string($mode) && '' !== $mode) {
            parse_str($mode, $runtime);
            if (isset($runtime['web']) && !\in_array($runtime['web'], ['', '0', 'false', 'off', 'no'], true)) {
                return true;
            }
        }

        return false;
    }
}
