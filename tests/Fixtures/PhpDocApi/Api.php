<?php

declare(strict_types=1);

namespace DocumentationFixture;

use DateTimeImmutable as Clock;

/** A documented public contract. */
interface Contract
{
    /** Send values.
     * @param list<string> $values Values to send.
     * @return list<string> The sent values.
     */
    public function send(array $values): array;
}

/** A reusable public trait. */
trait Greets
{
    /** Return an English greeting. */
    public function greet(): string { return 'Hello'; }
}

/** English **summary** with café and `code`.
 *
 * First paragraph with <literal> & "quotes".
 *
 * ```php
 * $path = "\\App\\Thing";
 * echo $path; // Ω
 * ```
 *
 * Last paragraph with {@see Contract}.
 *
 * @api
 * @example example.txt A source example.
 */
class PublicApi implements Contract
{
    use Greets;

    /** The public version. */
    public const string VERSION = 'v1';
    /** @internal */
    public const string HIDDEN = 'SECRET_CONSTANT';
    /** @var list<string> Items retained by the API. */
    protected array $items = [];
    /** @internal */
    public string $hidden = 'SECRET_PROPERTY';
    private string $private = 'SECRET_PRIVATE';

    /** Create an instance. */
    public function __construct(public readonly string $label = 'café') {}

    /** Combine a value with options.
     *
     * Preserve this paragraph.
     *
     * @param Clock|string $value The value, with Ω and \\ preserved.
     * @param array<string, string> $options Options with **Markdown**.
     * @return string|null The combined value, or null.
     * @throws \RuntimeException If combining fails.
     * @throws \LogicException If options are invalid.
     */
    final public static function  combine(
        Clock|string $value,
        array $options = ['brace' => '{;"}', 'path' => '\\App\\Thing'],
    ): ?string
    {
        return null;
    }

    public function send(array $values): array { return $values; }
    public function undocumented(): void {}
    /** @internal */
    public function hidden(): void { echo 'SECRET_METHOD'; }
    private function privateMethod(): void {}
}

/** @internal */
class InternalApi { public function secret(): void {} }

/** A public enum. */
enum Mode: string
{
    /** English mode. */
    case English = 'en';
}

/** A public function.
 * @return string Greeting text.
 */
function greeting(string $name = 'Ω'): string { return $name; }

/** A public global constant. */
const LABEL = 'café';
