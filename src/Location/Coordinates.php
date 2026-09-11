<?php

declare(strict_types=1);

namespace Nexum\Location;

final readonly class Coordinates
{
    public function __construct(public float $latitude, public float $longitude)
    {
        if (!is_finite($latitude) || !is_finite($longitude) || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw new \InvalidArgumentException('Coordinates are outside the valid WGS84 bounds.');
        }
    }
}
