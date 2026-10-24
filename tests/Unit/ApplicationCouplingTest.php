<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Architecture\ArchitectureAnalyzer;
use Sillbeam\Core\Architecture\ArchitectureReport;
use Sillbeam\Core\Architecture\DiscoveryOptions;
use Sillbeam\Core\Tests\Support\SourceWorkspace;

final class ApplicationCouplingTest extends TestCase
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

    public function testConstructorSeparatesModuleApplicationAndVendorReferencesWithoutChangingActivation(): void
    {
        $target = $this->source->php('src/Module/User/UserModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class UserModule {}');
        $service = $this->source->php('src/Module/User/UserService.php', 'class UserService {}');
        $mail = $this->source->php('src/Service/MailService.php', 'class MailService {}');
        $module = $this->source->php('src/Module/Checkout/CheckoutModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule(dependencies: [\\'.$target.'::class])] final class CheckoutModule {}');
        $this->source->php('src/Module/Checkout/Consumer.php', 'class Consumer { public function __construct(public \\'.$service.' $user, public \\'.$mail.' $mail, public \Psr\Log\LoggerInterface $logger) {} }');
        $report = $this->report();
        self::assertSame(0, $report->exitCode());
        $data = $this->module($report, $module);
        self::assertSame([$target], $data['declared_dependencies']);
        self::assertSame([$target], $data['observed_dependencies']);
        self::assertCount(1, $data['references']);
        self::assertSame([$mail], $data['application_dependencies']);
        self::assertSame([[
            'class' => $mail, 'file' => $this->source->directory.'/src/Module/Checkout/Consumer.php', 'line' => 1,
            'target_file' => $this->source->directory.'/src/Service/MailService.php',
        ]], $data['application_references']);
    }

    public function testApplicationEvidenceIsDeduplicatedPerLineAndLogicalTargetsAreUnique(): void
    {
        $mail = $this->source->php('src/Service/Mail.php', 'class Mail {}');
        $module = $this->source->php('src/Module/Checkout/CheckoutModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class CheckoutModule {}');
        $this->source->php('src/Module/Checkout/Consumer.php', 'class Consumer { public function run(\\'.$mail.' $mail): \\'.strtolower($mail).' { return new \\'.$mail.'(); }'."\n".'public function other(): string { return \\'.$mail.'::class; } }');
        $data = $this->module($this->report(), $module);
        self::assertSame([$mail], $data['application_dependencies']);
        self::assertCount(2, $data['application_references']);
        self::assertSame([1, 2], array_column($data['application_references'], 'line'));
    }

    public function testUndeclaredModuleDependencyStillFailsWhileApplicationCouplingRemainsInformational(): void
    {
        $this->source->php('src/Module/User/UserModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class UserModule {}');
        $service = $this->source->php('src/Module/User/UserService.php', 'class UserService {}');
        $mail = $this->source->php('src/Mail.php', 'class Mail {}');
        $module = $this->source->php('src/Module/Checkout/CheckoutModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class CheckoutModule {}');
        $this->source->php('src/Module/Checkout/Consumer.php', 'class Consumer { public function __construct(public \\'.$service.' $user, public \\'.$mail.' $mail) {} }');
        $report = $this->report();
        self::assertSame(1, $report->exitCode());
        self::assertSame(['code.undeclared_dependency'], array_column($report->data['findings'], 'rule'));
        self::assertSame([$mail], $this->module($report, $module)['application_dependencies']);
    }

    public function testInactiveModulesRetainEvidenceWithoutBeingActivated(): void
    {
        $mail = $this->source->php('src/Mail.php', 'class Mail {}');
        $module = $this->source->php('src/Module/Checkout/CheckoutModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class CheckoutModule {}');
        $this->source->php('src/Module/Checkout/Consumer.php', 'class Consumer { public \\'.$mail.' $mail; }');
        $data = $this->module($this->report(['modules' => ['checkout' => ['enabled' => false]]]), $module);
        self::assertFalse($data['active']);
        self::assertSame('disabled', $data['inactive_reason']);
        self::assertSame([$mail], $data['application_dependencies']);
    }

    public function testProjectPrivateBoundariesAndDisabledTargetsAreStillEnforced(): void
    {
        $this->source->php('src/Project/One/OneProject.php', '#[\Sillbeam\Core\Project\Attribute\SillbeamProject] final class OneProject {}');
        $this->source->php('src/Project/Two/TwoProject.php', '#[\Sillbeam\Core\Project\Attribute\SillbeamProject] final class TwoProject {}');
        $local = $this->source->php('src/Project/One/Module/Local/LocalModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class LocalModule {}');
        $this->source->php('src/Project/Two/Module/Secret/SecretModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class SecretModule {}');
        $secret = $this->source->php('src/Project/Two/Module/Secret/Port.php', 'interface Port {}');
        $mail = $this->source->php('src/Service/Mail.php', 'class Mail {}');
        $this->source->php('src/Project/One/Module/Local/Consumer.php', 'class Consumer { public function __construct(public \\'.$secret.' $secret, public \\'.$mail.' $mail) {} }');
        $config = ['projects' => ['one' => ['hosts' => ['one.test']], 'two' => ['hosts' => ['two.test']]]];
        $report = $this->report($config);
        self::assertSame(['code.private'], array_column($report->data['findings'], 'rule'));
        self::assertSame([$mail], $this->module($report, $local)['application_dependencies']);
        $config['modules'] = ['project:two:secret' => ['enabled' => false]];
        self::assertSame(['code.private'], array_column($this->report($config)->data['findings'], 'rule'));
    }

    public function testApplicationClassificationUsesFilesRatherThanNamespacePrefixes(): void
    {
        $class = 'Symfony\\Local'.bin2hex(random_bytes(5));
        $this->source->write('src/Service/Local.php', '<?php namespace Symfony; class '.substr($class, 8).' {}');
        $module = $this->source->php('src/Module/Checkout/CheckoutModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class CheckoutModule {}');
        $this->source->php('src/Module/Checkout/Consumer.php', 'class Consumer { public \\'.$class.' $local; }');
        self::assertSame([$class], $this->module($this->report(), $module)['application_dependencies']);
    }

    public function testDisabledSharedTargetRemainsAModuleViolation(): void
    {
        $target = $this->source->php('src/Module/User/UserModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class UserModule {}');
        $module = $this->source->php('src/Module/Checkout/CheckoutModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class CheckoutModule {}');
        $this->source->php('src/Module/Checkout/Consumer.php', 'class Consumer { public \\'.$target.' $target; }');
        $report = $this->report(['modules' => ['user' => ['enabled' => false]]]);
        self::assertSame(['code.inactive'], array_column($report->data['findings'], 'rule'));
        self::assertSame([$target], $this->module($report, $module)['observed_dependencies']);
        self::assertArrayNotHasKey('application_dependencies', $this->module($report, $module));
    }

    public function testComposerTargetOutsideTheSourceIndexIsNotClassifiedByItsApplicationNamespace(): void
    {
        $class = $this->source->namespace.'\\Service\\External';
        $this->source->write('external.php', '<?php namespace '.$this->source->namespace.'\\Service; throw new \\RuntimeException("Do not execute"); class External {}');
        $loader = new \Composer\Autoload\ClassLoader();
        $loader->addClassMap([$class => $this->source->directory.'/external.php']);
        $loader->register(true);
        try {
            $module = $this->source->php('src/Module/Checkout/CheckoutModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class CheckoutModule {}');
            $this->source->php('src/Module/Checkout/Consumer.php', 'class Consumer { public \\'.$class.' $external; }');
            $report = $this->report();
            self::assertSame(0, $report->exitCode());
            self::assertSame([], $this->module($report, $module)['observed_dependencies']);
            self::assertArrayNotHasKey('application_references', $this->module($report, $module));
            self::assertFalse(class_exists($class, false));
        } finally {
            $loader->unregister();
        }
    }

    /** @param array<string, mixed> $config */
    private function report(array $config = []): ArchitectureReport
    {
        return ArchitectureReport::fromAnalysis((new ArchitectureAnalyzer())->analyze(new DiscoveryOptions($this->source->directory.'/src', $this->source->namespace.'\\'), $config), $config);
    }

    /** @return array<string, mixed> */
    private function module(ArchitectureReport $report, string $class): array
    {
        foreach ($report->data['modules'] as $module) {
            if ($module['class'] === $class) {
                return $module;
            }
        }
        self::fail('Module absent from report.');
    }
}
