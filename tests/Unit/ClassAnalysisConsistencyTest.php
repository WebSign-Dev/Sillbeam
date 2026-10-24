<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Architecture\ArchitectureAnalyzer;
use Sillbeam\Core\Architecture\ArchitectureReport;
use Sillbeam\Core\Architecture\DiscoveryOptions;
use Sillbeam\Core\Architecture\DiscoveryOptionsResolver;
use Sillbeam\Core\Architecture\DiscoveryRoot;
use Sillbeam\Core\Tests\Support\SourceWorkspace;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ClassAnalysisConsistencyTest extends TestCase
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

    /** @param list<string> $rules */
    #[DataProvider('moduleScenarios')]
    public function testCodeAndDeclaredModuleRulesRemainDistinct(bool $declared, bool $enabled, bool $cycle, array $rules): void
    {
        $a = $this->source->namespace.'\\Module\\A\\AModule';
        $b = $this->source->namespace.'\\Module\\B\\BModule';
        $this->source->php('src/Module/A/AModule.php', \sprintf('#[\Sillbeam\Core\Module\Attribute\SillbeamModule(dependencies: [%s])] final class AModule {}', $declared ? '\\'.$b.'::class' : ''));
        $this->source->php('src/Module/B/BModule.php', \sprintf('#[\Sillbeam\Core\Module\Attribute\SillbeamModule(dependencies: [%s])] final class BModule {}', $cycle ? '\\'.$a.'::class' : ''));
        $port = $this->source->php('src/Module/B/Port.php', 'interface Port {}');
        $mail = $this->source->php('src/Service/Mail.php', 'class Mail {}');
        $this->source->php('src/Module/A/Consumer.php', \sprintf('class Consumer { public function __construct(public \\%s $port, public \\%s $mail, public \Doctrine\Persistence\ManagerRegistry $doctrine, public \Psr\Log\LoggerInterface $logger) {} public function port(): \\%s { return $this->port; } }', $port, $mail, $port));
        $this->source->php('src/Module/A/Controller.php', 'use Symfony\Component\HttpFoundation\Response; class Controller {
public function first(): Response { return new Response(); }
public function second(): Response { return new Response(); }
}');
        $report = $this->report(['modules' => ['b' => ['enabled' => $enabled]]]);
        self::assertSame($rules, array_column($report->data['findings'], 'rule'));
        self::assertSame([] === $rules ? 0 : 1, $report->exitCode());
        $module = array_column($report->data['modules'], null, 'class')[$a];
        self::assertSame($declared ? [$b] : [], $module['declared_dependencies']);
        self::assertSame([$b], $module['observed_dependencies']);
        self::assertCount(1, $module['references']);
        self::assertSame([$mail], $module['application_dependencies']);
        self::assertCount(1, $module['application_references']);
    }

    /** @return iterable<string, array{bool, bool, bool, list<string>}> */
    public static function moduleScenarios(): iterable
    {
        yield 'undeclared module access' => [false, true, false, ['code.undeclared_dependency']];
        yield 'declared module access' => [true, true, false, []];
        yield 'inactive code target' => [false, false, false, ['code.inactive']];
        yield 'disabled declared target and code target' => [true, false, false, ['code.inactive', 'dependency.disabled']];
        yield 'declared cycle' => [true, true, true, ['dependency.cycle']];
    }

    public function testExternalClassExplicitlyDeclaredAsModuleRemainsAnUnknownDependency(): void
    {
        $module = $this->source->php('src/Module/A/AModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule(dependencies: [\Symfony\Component\HttpFoundation\Response::class, \Symfony\Component\HttpFoundation\Response::class])] final class AModule {}');
        $this->source->php('src/Module/A/Controller.php', 'class Controller { public function response(): \Symfony\Component\HttpFoundation\Response { return new \Symfony\Component\HttpFoundation\Response(); } }');
        $report = $this->report();
        self::assertSame(['dependency.unknown'], array_column($report->data['findings'], 'rule'));
        self::assertSame(\Symfony\Component\HttpFoundation\Response::class, $report->data['findings'][0]['context']['target']);
        $data = array_column($report->data['modules'], null, 'class')[$module];
        self::assertSame([], $data['observed_dependencies']);
        self::assertSame([], $data['references']);
    }

    public function testExplicitPrivateDependencyDoesNotPermitSharedCodeAccess(): void
    {
        $a = $this->source->namespace.'\\Module\\A\\AModule';
        $secret = $this->source->namespace.'\\Project\\Shop\\Module\\Secret\\SecretModule';
        $this->source->php('src/Module/A/AModule.php', \sprintf('#[\Sillbeam\Core\Module\Attribute\SillbeamModule(dependencies: [\\%s::class])] final class AModule {}', $secret));
        $this->source->php('src/Project/Shop/ShopProject.php', \sprintf('#[\Sillbeam\Core\Project\Attribute\SillbeamProject(modules: [\\%s::class])] final class ShopProject {}', $a));
        $this->source->php('src/Project/Shop/Module/Secret/SecretModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class SecretModule {}');
        $port = $this->source->php('src/Project/Shop/Module/Secret/Port.php', 'interface Port {}');
        $this->source->php('src/Module/A/Consumer.php', \sprintf('class Consumer { public function __construct(public \\%s $port, public \Psr\Log\LoggerInterface $logger) {} }', $port));
        self::assertSame(['code.private', 'dependency.private'], array_column($this->report()->data['findings'], 'rule'));
    }

    public function testIndexedModuleWithVendorLikeNamespaceStillUndergoesModuleChecks(): void
    {
        $namespace = 'Symfony\\LocalAudit'.bin2hex(random_bytes(5));
        foreach (['A', 'B'] as $name) {
            $this->source->write('modules/'.$name.'/'.$name.'Module.php', \sprintf('<?php namespace %s\%s; #[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class %sModule {}', $namespace, $name, $name));
        }
        $this->source->write('modules/B/Port.php', \sprintf('<?php namespace %s\B; interface Port {}', $namespace));
        $this->source->write('modules/A/Consumer.php', \sprintf('<?php namespace %s\A; class Consumer { public function __construct(public \%s\B\Port $port, public \Symfony\Component\HttpFoundation\Response $response) {} }', $namespace, $namespace));
        $options = new DiscoveryOptions($this->source->directory.'/src', $this->source->namespace.'\\', moduleRoots: [new DiscoveryRoot($this->source->directory.'/modules', $namespace.'\\')]);
        $result = (new ArchitectureAnalyzer())->analyze($options);
        self::assertSame(['code.undeclared_dependency'], array_column($result->findings, 'rule'));
        self::assertSame($namespace.'\\B\\BModule', $result->findings[0]->context['target']);
    }

    /** @param array<string, mixed> $config */
    private function report(array $config = []): ArchitectureReport
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->source->directory);
        $options = (new DiscoveryOptionsResolver())->resolve($container, []);

        return ArchitectureReport::fromAnalysis((new ArchitectureAnalyzer())->analyze($options, $config), $config);
    }
}
