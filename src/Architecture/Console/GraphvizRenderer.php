<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture\Console;

/**
 * Optional CLI-only Graphviz process. Uses an argument array and file streams to
 * avoid shell interpolation and pipe deadlocks for large inputs/diagnostics.
 *
 * @internal
 */
final readonly class GraphvizRenderer
{
    public function __construct(private string $executable = 'dot')
    {
    }

    /**
     * Returns complete SVG after successful rendering. Never writes an output destination.
     *
     * @throws \RuntimeException when dot is unavailable, times out, fails or produces no SVG
     */
    public function render(string $dot): string
    {
        if (!\function_exists('proc_open')) {
            throw new \RuntimeException('Graphviz rendering requires PHP proc_open to be enabled.');
        }
        $input = @tmpfile();
        $output = @tmpfile();
        $errors = @tmpfile();
        if (false === $input || false === $output || false === $errors) {
            foreach ([$input, $output, $errors] as $stream) {
                if (\is_resource($stream)) {
                    fclose($stream);
                }
            }
            throw new \RuntimeException('Cannot allocate temporary Graphviz streams.');
        }
        $process = null;
        try {
            if (\strlen($dot) !== @fwrite($input, $dot) || !@rewind($input)) {
                throw new \RuntimeException('Cannot prepare Graphviz input.');
            }
            $process = @proc_open([$this->executable, '-Tsvg'], [$input, $output, $errors], $pipes, null, null, ['bypass_shell' => true]);
            if (!\is_resource($process)) {
                throw new \RuntimeException('Cannot start Graphviz dot. Please install the optional Graphviz renderer and ensure dot is executable on PATH.');
            }
            $deadline = microtime(true) + 60;
            while (true) {
                $status = proc_get_status($process);
                if (!$status['running']) {
                    break;
                }
                if (microtime(true) >= $deadline) {
                    proc_terminate($process, 9);
                    throw new \RuntimeException('Graphviz dot exceeded the 60-second rendering limit. Try --module to inspect direct relationships.');
                }
                usleep(10000);
            }
            $exit = proc_close($process);
            $process = null;
            $exit = -1 === $exit ? $status['exitcode'] : $exit;
            rewind($errors);
            $diagnostics = stream_get_contents($errors, 8192);
            if (0 !== $exit) {
                throw new \RuntimeException('Graphviz dot failed (exit '.$exit.'). Please install the optional Graphviz renderer and ensure dot is executable on PATH. '.($diagnostics ?: 'No renderer diagnostics.'));
            }
            rewind($output);
            $svg = stream_get_contents($output);
            if (false === $svg || !preg_match('/<svg\b[^>]*\bxmlns="http:\/\/www\.w3\.org\/2000\/svg"[^>]*>/s', $svg) || !str_ends_with(rtrim($svg), '</svg>')) {
                throw new \RuntimeException('Graphviz dot did not produce a complete SVG document.');
            }

            return $svg;
        } finally {
            if (\is_resource($process)) {
                proc_terminate($process, 9);
                proc_close($process);
            }
            fclose($input);
            fclose($output);
            fclose($errors);
        }
    }
}
