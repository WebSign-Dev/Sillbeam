<?php

declare(strict_types=1);

namespace Nexum\Security\Mfa\Crypto;

final readonly class SecretCipher
{
    private string $key;

    public function __construct(#[\SensitiveParameter] string $applicationSecret)
    {
        if (\strlen($applicationSecret) < 16) {
            throw new \InvalidArgumentException('APP_SECRET must contain at least 16 characters when MFA is enabled.');
        }

        $this->key = sodium_crypto_generichash(
            'nexum-security-mfa\0'.$applicationSecret,
            '',
            \SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
        );
    }

    public function encrypt(#[\SensitiveParameter] string $plainText): string
    {
        $nonce = random_bytes(\SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipherText = sodium_crypto_secretbox($plainText, $nonce, $this->key);

        return base64_encode($nonce.$cipherText);
    }

    public function decrypt(#[\SensitiveParameter] string $encoded): string
    {
        $payload = base64_decode($encoded, true);

        if (false === $payload || \strlen($payload) <= \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new \RuntimeException('The encrypted MFA secret is invalid.');
        }

        $plainText = sodium_crypto_secretbox_open(
            substr($payload, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            substr($payload, 0, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            $this->key,
        );

        if (false === $plainText) {
            throw new \RuntimeException('The encrypted MFA secret could not be authenticated.');
        }

        return $plainText;
    }

    public function keyedHash(#[\SensitiveParameter] string $value): string
    {
        return sodium_bin2hex(sodium_crypto_generichash($value, $this->key));
    }
}
