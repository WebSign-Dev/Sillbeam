<?php

declare(strict_types=1);

namespace Nexum\Location;

final readonly class PostalAddress
{
    public function __construct(
        public string $street,
        public string $postalCode,
        public string $city,
        public string $countryCode,
    ) {
        if ('' === trim($street) || '' === trim($postalCode) || '' === trim($city) || 1 !== preg_match('/^[A-Z]{2}$/', $countryCode)) {
            throw new \InvalidArgumentException('Invalid postal address.');
        }
    }
}
