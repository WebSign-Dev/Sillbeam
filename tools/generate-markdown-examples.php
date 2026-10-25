<?php

declare(strict_types=1);

// Developer-only reproducible examples: every document comes from an actual analysis.
require dirname(__DIR__).'/vendor/autoload.php';

use Composer\InstalledVersions;
use Sillbeam\Core\Architecture\ArchitectureAnalyzer;
use Sillbeam\Core\Architecture\ArchitectureReport;
use Sillbeam\Core\Architecture\Console\ReportRenderer;
use Sillbeam\Core\Architecture\DiscoveryOptions;

$root = sys_get_temp_dir().'/sillbeam-markdown-examples-'.bin2hex(random_bytes(4));
$output = $argv[1] ?? dirname(__DIR__).'/docs/examples/architecture-reports';
if (!is_dir($output) && !mkdir($output, 0777, true)) {
    throw new RuntimeException('Cannot create example output directory.');
}
$currentNamespace = 'ExampleShop';
$write = static function (string $path, string $text) use (&$currentNamespace): void {
    $text = str_replace('ExampleShop', $currentNamespace, $text);
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }
    file_put_contents($path, $text);
};
$module = static function (string $project, string $name, array $dependencies = [], ?string $owner = null) use ($write): void {
    $relative = null === $owner ? 'Module/'.$name : 'Project/'.$owner.'/Module/'.$name;
    $namespace = 'ExampleShop\\'.str_replace('/', '\\', $relative);
    $write($project.'/src/'.$relative.'/'.$name.'Module.php', '<?php namespace '.$namespace.'; #[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule(dependencies: ['.implode(', ', array_map(static fn (string $class): string => '\\'.$class.'::class', $dependencies)).'])] final class '.$name.'Module {}');
};
$class = static fn (string $name): string => 'ExampleShop\\Module\\'.$name.'\\'.$name.'Module';
$metadata = [];
foreach (['valid', 'single-error', 'multiple-errors', 'incomplete', 'large', 'duplicate-names'] as $scenario) {
    $currentNamespace = 'ExampleShop'.str_replace(' ', '', ucwords(str_replace('-', ' ', $scenario)));
    $project = $root.'/'.$scenario;
    $write($project.'/composer.json', json_encode(['autoload' => ['psr-4' => ['ExampleShop\\' => 'src/']]], \JSON_THROW_ON_ERROR));
    foreach (['User' => [], 'Catalog' => [], 'AgeGroup' => [$class('User')], 'Portrait' => [$class('Catalog'), $class('AgeGroup'), $class('User')], 'Admin' => [$class('User'), $class('Catalog')]] as $name => $dependencies) {
        $module($project, $name, $dependencies);
    }
    foreach (['Shop' => ['Portrait', 'Admin'], 'Office' => ['Admin']] as $owner => $names) {
        $dependencies = implode(', ', array_map(static fn (string $name): string => '\\'.$class($name).'::class', $names));
        $descriptor = 'duplicate-names' === $scenario ? 'Shop' : $owner;
        $id = 'duplicate-names' === $scenario ? 'id: "'.strtolower($owner).'", ' : '';
        $write($project.'/src/Project/'.$owner.'/'.$descriptor.'Project.php', '<?php namespace ExampleShop\\Project\\'.$owner.'; #[\\Sillbeam\\Core\\Project\\Attribute\\SillbeamProject('.$id.'modules: ['.$dependencies.'])] final class '.$descriptor.'Project {}');
        $module($project, 'AgeGroup', [$class('User')], $owner);
    }
    $write($project.'/src/Module/Catalog/Item.php', '<?php namespace ExampleShop\\Module\\Catalog; final class Item {}');
    $write($project.'/src/Service/Mail.php', '<?php namespace ExampleShop\\Service; final class Mail {}');
    $write($project.'/src/Module/Admin/MailConsumer.php', '<?php namespace ExampleShop\\Module\\Admin; final class MailConsumer { public \\ExampleShop\\Service\\Mail $mail; }');
    if (in_array($scenario, ['single-error', 'multiple-errors'], true)) {
        $write($project.'/src/Module/User/Consumer.php', '<?php namespace ExampleShop\\Module\\User; final class Consumer { public \\ExampleShop\\Module\\Catalog\\Item $item; }');
    }
    if ('multiple-errors' === $scenario) {
        $write($project.'/src/Module/User/Second.php', '<?php namespace ExampleShop\\Module\\User; final class Second { public \\ExampleShop\\Module\\Catalog\\Item $item; public \\ExampleShop\\MissingRenderer $renderer; }');
    }
    if ('incomplete' === $scenario) {
        $write($project.'/src/Broken.php', '<?php class {');
    }
    if ('large' === $scenario) {
        $dependencies = [];
        foreach (['Payments', 'Inventory', 'Fulfilment', 'Returns', 'Notifications', 'Delivery', 'Shipping', 'Taxation', 'AddressBook', 'Loyalty', 'Promotions', 'Analytics'] as $name) {
            $module($project, $name);
            $dependencies[] = $class($name);
        }
        $module($project, 'InternationalPaymentReconciliationAndSettlementCoordinator', $dependencies);
    }
    $config = ['projects' => ['shop' => ['hosts' => ['shop.example.test']], 'office' => ['hosts' => ['office.example.test']]]];
    if ('large' === $scenario) {
        $config['modules']['shared:payments']['enabled'] = false;
    }
    $analysis = (new ArchitectureAnalyzer())->analyze(new DiscoveryOptions($project.'/src', $currentNamespace.'\\'), $config);
    $report = ArchitectureReport::fromAnalysis($analysis, $config, $project);
    $expected = 'incomplete' === $scenario ? 'unavailable' : (in_array($scenario, ['single-error', 'multiple-errors'], true) ? 'violations' : 'valid');
    if ($report->data['status'] !== $expected) {
        throw new RuntimeException('Unexpected example status: '.$scenario.' '.$report->toJson());
    }
    $version = InstalledVersions::getPrettyVersion('websign-dev/sillbeam-core') ?? 'source';
    $checked = new DateTimeImmutable();
    $renderer = new ReportRenderer($report);
    file_put_contents($output.'/'.$scenario.'.md', $renderer->markdown($version, $checked));
    if ('valid' === $scenario) {
        file_put_contents($output.'/valid-with-couplings.md', $renderer->markdown($version, $checked, true));
    }
    $metadata[$scenario] = ['source' => $project, 'status' => $report->data['status'], 'findings' => count($report->data['findings']), 'diagnoses' => count($report->presentation['issues'])];
    echo $scenario.': '.$report->data['status'].' / '.count($report->data['findings']).' findings'.\PHP_EOL;
}
echo 'Source fixtures retained under '.$root.\PHP_EOL;
file_put_contents($root.'/generation.json', json_encode($metadata, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR));
