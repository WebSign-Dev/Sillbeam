<?php

declare(strict_types=1);

namespace Nexum\QrCode\Renderer;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Nexum\QrCode\Contract\QrCodeGeneratorInterface;

final readonly class EndroidQrCodeGenerator implements QrCodeGeneratorInterface
{
    public function __construct(private int $maxDataBytes = 4096)
    {
    }

    public function svg(string $data, int $size = 300): string
    {
        if ('' === $data || \strlen($data) > $this->maxDataBytes) {
            throw new \InvalidArgumentException('QR-code data must contain between 1 and 4096 bytes.');
        }
        if ($size < 64 || $size > 2048) {
            throw new \InvalidArgumentException('QR-code size must be between 64 and 2048 pixels.');
        }

        return (new Builder(writer: new SvgWriter(), data: $data, size: $size, margin: 10))->build()->getString();
    }
}
