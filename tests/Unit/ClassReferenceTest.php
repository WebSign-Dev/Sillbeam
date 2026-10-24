<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Unit;

use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Architecture\ArchitectureAnalyzer;
use Sillbeam\Core\Architecture\DiscoveryOptions;
use Sillbeam\Core\Tests\Support\SourceWorkspace;

final class ClassReferenceTest extends TestCase
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

    public function testMissingConstructorTypeIsReportedAlongsideUnknownDeclaredDependency(): void
    {
        $this->source->php('src/Module/Hello/HelloModule.php', str_replace('__APP__', $this->source->namespace, <<<'CODE'
#[\Sillbeam\Core\Module\Attribute\SillbeamModule(dependencies: [\Missing\UnknownModule::class])]
final class HelloModule {
    public function __construct(private \__APP__\Missing\ConstructorType $missing) {}
}
CODE));
        $result = $this->analyze();
        self::assertSame(['code.unknown_class', 'dependency.unknown'], array_column($result->findings, 'rule'));
        $finding = $result->findings[0];
        self::assertSame($this->source->namespace.'\Missing\ConstructorType', $finding->context['class']);
        self::assertSame($this->source->directory.'/src/Module/Hello/HelloModule.php', $finding->context['file']);
        self::assertSame(3, $finding->context['line']);
        self::assertSame($this->source->namespace.'\Module\Hello\HelloModule', $finding->context['module']);
        self::assertNotNull($finding->suggestion());
        self::assertTrue($result->isComplete());
        self::assertFalse($result->isValid());
    }

    public function testIndexedClassesInterfacesTraitsEnumsAndBuiltinTypesAreAccepted(): void
    {
        $this->source->php('src/Module/Hello/HelloModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class HelloModule {}');
        $this->source->php('src/Module/Hello/Types.php', 'interface Port {} trait Mix {} enum State { case Ready; } class Base {}');
        $this->source->php('src/Module/Hello/Consumer.php', 'class Consumer extends Base implements Port { use Mix; public function __construct(public State $state, public \DateTimeInterface $time, public \Throwable $error, public \Symfony\Component\Console\Input\InputInterface $input) {} }');
        self::assertTrue($this->analyze()->isValid());
    }

    public function testComposerPsr4AndClassmapReferencesDoNotExecuteTargetFiles(): void
    {
        $namespace = $this->source->namespace.'External';
        $this->source->write('external/Port.php', '<?php namespace '.$namespace.'; throw new \RuntimeException("Do not execute"); interface Port {}');
        $this->source->write('mapped.php', '<?php namespace '.$namespace.'; throw new \RuntimeException("Do not execute"); class Mapped {}');
        $loader = new ClassLoader();
        $loader->addPsr4($namespace.'\\', $this->source->directory.'/external');
        $loader->addClassMap([$namespace.'\Mapped' => $this->source->directory.'/mapped.php']);
        $loader->register(true);
        try {
            $this->source->php('src/Module/Hello/HelloModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class HelloModule { public function __construct(public \\'.$namespace.'\Port $port, public \\'.$namespace.'\Mapped $mapped) {} }');
            self::assertTrue($this->analyze()->isValid());
            self::assertFalse(interface_exists($namespace.'\Port', false));
            self::assertFalse(class_exists($namespace.'\Mapped', false));
        } finally {
            $loader->unregister();
        }
    }

    public function testProjectReferencesAreCheckedAndNonArchitectureCodeIsNot(): void
    {
        $this->source->php('src/Project/Shop/ShopProject.php', '#[\Sillbeam\Core\Project\Attribute\SillbeamProject] final class ShopProject { public function __construct(public \\'.$this->source->namespace.'\Missing\ProjectType $missing) {} }');
        $this->source->php('src/Outside.php', 'class Outside { public \Missing\Outside $missing; }');
        $result = $this->analyze();
        self::assertCount(1, $result->findings);
        self::assertSame('code.unknown_class', $result->findings[0]->rule);
        self::assertSame($this->source->namespace.'\Project\Shop\ShopProject', $result->findings[0]->context['project']);
    }

    public function testMissingReferencesAreCheckedInDisabledModulesAndDeduplicatedPerLine(): void
    {
        $this->source->php('src/Module/Hello/HelloModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class HelloModule {}');
        $missing = $this->source->namespace.'\\Missing\\Type';
        $this->source->php('src/Module/Hello/Consumer.php', 'class Consumer { public function run(\\'.$missing.' $type): \\'.strtolower($missing).' { return new \\'.$missing.'(); } }');
        $result = $this->analyze(['modules' => ['hello' => ['enabled' => false]]]);
        self::assertCount(1, $result->findings);
        self::assertSame('code.unknown_class', $result->findings[0]->rule);
    }

    public function testOptionalAttributeNamesAndDevelopmentHelpersDoNotRequirePackages(): void
    {
        $this->source->php('src/Module/Hello/HelloModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class HelloModule {}');
        $this->source->php('src/Module/Hello/Record.php', '#[\OptionalPackage\Entity] class Record { #[\OptionalPackage\Column] public string $name; }');
        $this->source->php('src/Module/Hello/Tests/Helper.php', 'class Helper extends \UnavailableTestPackage\TestCase {}');
        $this->source->php('src/Module/Hello/Nested/Fixtures/Helper.php', 'class Helper extends \UnavailableTestPackage\Fixture {}');
        self::assertTrue($this->analyze()->isValid());
        $this->source->php('src/Module/Hello/Record.php', '#[\OptionalPackage\Entity] class Record { public function __construct(public \\'.$this->source->namespace.'\Missing\RequiredType $required) {} }');
        $result = $this->analyze();
        self::assertCount(1, $result->findings);
        self::assertSame($this->source->namespace.'\\Missing\\RequiredType', $result->findings[0]->context['class']);
    }

    /** @param array<string, mixed> $config */
    private function analyze(array $config = []): \Sillbeam\Core\Architecture\ArchitectureAnalysisResult
    {
        return (new ArchitectureAnalyzer())->analyze(new DiscoveryOptions($this->source->directory.'/src', $this->source->namespace.'\\'), $config);
    }
}
