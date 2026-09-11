<?php

declare(strict_types=1);

namespace Nexum\QrCode\Contract;

interface QrCodeGeneratorInterface
{
    public function svg(string $data, int $size = 300): string;
}
