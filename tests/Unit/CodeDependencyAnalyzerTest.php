<?php

declare(strict_types=1);

namespace Sillbeam\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sillbeam\Architecture\ArchitectureCompiler;
use Sillbeam\Architecture\ArchitectureDiscovery;
use Sillbeam\Architecture\CodeDependencyAnalyzer;
use Sillbeam\Architecture\DiscoveryOptions;
use Sillbeam\Architecture\SourceInspector;
use Sillbeam\Tests\Support\SourceWorkspace;

final class CodeDependencyAnalyzerTest extends TestCase
{
    private SourceWorkspace $source;

    protected function setUp(): void
    {
        $this->source = new SourceWorkspace();
    }

    protected function tearDown(): void
    {
        $this->source->remove();
    }

    /** @param array<string, mixed> $config
     * @return array<string, list<array{module: string, class: string, file: string, line: int}>>
     */
    private function analyze(array $config = []): array
    {
        $options = new DiscoveryOptions($this->source->directory.'/src', $this->source->namespace.'\\');
        $inspector = new SourceInspector();
        $discovery = (new ArchitectureDiscovery($inspector))->discover($options);
        $graph = (new ArchitectureCompiler())->compile($discovery, $config);

        return (new CodeDependencyAnalyzer($inspector))->analyze($discovery, $graph, $options);
    }

    /** @return array{class-string, class-string, class-string} */
    private function modules(string $dependencies = '[]'): array
    {
        $payment = $this->source->php('src/Module/Payment/PaymentModule.php', '#[\\Sillbeam\\Module\\Attribute\\SillbeamModule] final class PaymentModule {}');
        $billing = $this->source->php('src/Module/Billing/BillingModule.php', '#[\\Sillbeam\\Module\\Attribute\\SillbeamModule(dependencies: '.$dependencies.')] final class BillingModule {}');
        $target = $this->source->php('src/Module/Payment/Port.php', 'interface Port {}');

        return [$billing, $payment, $target];
    }

    public function testUndeclaredAliasedTypeReferenceFailsWithSourceEvidence(): void
    {
        [, , $target] = $this->modules();
        $this->source->php('src/Module/Billing/Consumer.php', 'use '.$target.' as ExternalPort; final class Consumer { public function __construct(public ExternalPort $port) {} }');
        $this->expectExceptionMessage('Consumer.php:1');
        $this->analyze();
    }

    public function testDeclaredDirectDependencyReportsActualReference(): void
    {
        $payment = $this->source->namespace.'\\Module\\Payment\\PaymentModule';
        [$billing, , $target] = $this->modules('[\\'.$payment.'::class]');
        $this->source->php('src/Module/Billing/Consumer.php', 'final class Consumer { public function make(): \\'.$target.' { throw new \\RuntimeException(); } }');
        $references = $this->analyze();
        self::assertSame($payment, $references[$billing][0]['module']);
        self::assertSame($target, $references[$billing][0]['class']);
        self::assertSame(1, $references[$billing][0]['line']);
    }

    public function testUnusedImportsAndDescriptorMetadataAreNotActualDependencies(): void
    {
        $payment = $this->source->namespace.'\\Module\\Payment\\PaymentModule';
        [$billing, , $target] = $this->modules('[\\'.$payment.'::class]');
        $this->source->php('src/Module/Billing/Consumer.php', 'use '.$target.'; final class Consumer {}');
        self::assertSame([], $this->analyze()[$billing]);
    }

    public function testTransitiveDeclarationDoesNotPermitDirectCodeAccess(): void
    {
        $payment = $this->source->namespace.'\\Module\\Payment\\PaymentModule';
        [$billing, , $target] = $this->modules();
        $bridge = $this->source->php('src/Module/Bridge/BridgeModule.php', '#[\\Sillbeam\\Module\\Attribute\\SillbeamModule(dependencies: [\\'.$payment.'::class])] final class BridgeModule {}');
        $this->source->php('src/Module/Billing/BillingModule.php', '#[\\Sillbeam\\Module\\Attribute\\SillbeamModule(dependencies: [\\'.$bridge.'::class])] final class BillingModule {}');
        $this->source->php('src/Module/Billing/Consumer.php', 'final class Consumer { public function get(): string { return \\'.$target.'::class; } }');
        $this->expectExceptionMessage('does not declare');
        $this->analyze();
    }

    public function testActiveCodeCannotUseDisabledModule(): void
    {
        [, , $target] = $this->modules();
        $this->source->php('src/Module/Billing/Consumer.php', 'final class Consumer { public \\'.$target.' $port; }');
        $this->expectExceptionMessage('inactive module');
        $this->analyze(['modules' => ['payment' => ['enabled' => false]]]);
    }

    public function testSharedCodeCannotUsePrivateProjectCode(): void
    {
        [$billing] = $this->modules();
        $this->source->php('src/Project/Shop/ShopProject.php', '#[\\Sillbeam\\Project\\Attribute\\SillbeamProject(modules: [\\'.$billing.'::class])] final class ShopProject {}');
        $this->source->php('src/Project/Shop/Module/Secret/SecretModule.php', '#[\\Sillbeam\\Module\\Attribute\\SillbeamModule] final class SecretModule {}');
        $secret = $this->source->php('src/Project/Shop/Module/Secret/Secret.php', 'final class Secret {}');
        $this->source->php('src/Module/Billing/Consumer.php', 'final class Consumer { public function make(): object { return new \\'.$secret.'(); } }');
        $this->expectExceptionMessage('private module');
        $this->analyze();
    }

    public function testAnonymousInheritanceIsAnActualDependency(): void
    {
        [, , $target] = $this->modules();
        $this->source->php('src/Module/Billing/Factory.php', 'final class Factory { public function make(): object { return new class implements \\'.$target.' {}; } }');
        $this->expectExceptionMessage('does not declare');
        $this->analyze(['projects' => ['one' => ['hosts' => ['one.test']], 'two' => ['hosts' => ['two.test']]]]);
    }

    public function testPrivateCodeCannotUseAnotherProjectsPrivateModule(): void
    {
        $this->source->php('src/Project/One/OneProject.php', '#[\\Sillbeam\\Project\\Attribute\\SillbeamProject] final class OneProject {}');
        $this->source->php('src/Project/Two/TwoProject.php', '#[\\Sillbeam\\Project\\Attribute\\SillbeamProject] final class TwoProject {}');
        $this->source->php('src/Project/One/Module/Local/LocalModule.php', '#[\\Sillbeam\\Module\\Attribute\\SillbeamModule] final class LocalModule {}');
        $this->source->php('src/Project/Two/Module/Remote/RemoteModule.php', '#[\\Sillbeam\\Module\\Attribute\\SillbeamModule] final class RemoteModule {}');
        $target = $this->source->php('src/Project/Two/Module/Remote/Port.php', 'interface Port {}');
        $this->source->php('src/Project/One/Module/Local/Consumer.php', 'final class Consumer { public \\'.$target.' $port; }');
        $this->expectExceptionMessage('private module');
        $this->analyze(['projects' => ['one' => ['hosts' => ['one.test']], 'two' => ['hosts' => ['two.test']]]]);
    }

    public function testExecutableCodeInDescriptorStillNeedsDirectDependencies(): void
    {
        [, , $target] = $this->modules();
        $this->source->php('src/Module/Billing/BillingModule.php', '#[\\Sillbeam\\Module\\Attribute\\SillbeamModule] final class BillingModule { public function target(): string { return \\'.$target.'::class; } }');
        $this->expectExceptionMessage('does not declare');
        $this->analyze();
    }
}
