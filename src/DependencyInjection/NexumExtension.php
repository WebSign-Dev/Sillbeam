<?php

declare(strict_types=1);

namespace Nexum\DependencyInjection;

use Nexum\Architecture\ArchitectureCompiler;
use Nexum\Architecture\ArchitectureDiscovery;
use Nexum\Architecture\CompiledArchitecture;
use Nexum\Architecture\DiscoveryOptionsResolver;
use Nexum\Architecture\DiscoveryResult;
use Nexum\Doctrine\Filter\SoftDeleteFilter;
use Nexum\Entity\AbstractEntity;
use Nexum\Filesystem\Adapter\LocalStorage;
use Nexum\Filesystem\Contract\StorageInterface;
use Nexum\Kernel\Command\AboutCommand;
use Nexum\Kernel\Runtime\KernelInformation;
use Nexum\Kernel\Runtime\KernelInformationInterface;
use Nexum\Mailer\Contract\MailerInterface;
use Nexum\Mailer\SymfonyMailer;
use Nexum\Module\ModuleRegistry;
use Nexum\Module\ModuleRegistryInterface;
use Nexum\Project\ProjectContext;
use Nexum\Project\ProjectContextInterface;
use Nexum\Project\ProjectRegistry;
use Nexum\Project\ProjectRegistryInterface;
use Nexum\QrCode\Contract\QrCodeGeneratorInterface;
use Nexum\QrCode\Renderer\EndroidQrCodeGenerator;
use Nexum\Routing\NexumRouteLoader;
use Nexum\Security\Contract\SubjectUserInterface;
use Nexum\User\Entity\ApiAccessToken;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

final class NexumExtension extends Extension implements PrependExtensionInterface
{
    private ?DiscoveryResult $discovery = null;

    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $options = (new DiscoveryOptionsResolver())->resolve($container, $configs);
        $this->discovery = (new ArchitectureDiscovery())->discover($options);
        $container->addResource(new DirectoryResource($options->root));
        foreach ($this->discovery->resources as $resource) {
            if (is_file($resource)) {
                $container->addResource(new FileResource($resource));
            }
        }

        $raw = $this->mergeRaw($configs);
        $container->prependExtensionConfig('framework', [
            'serializer' => ['enabled' => true],
            'validation' => ['enabled' => true],
        ]);
        if ($raw['security']['mfa']['enabled'] ?? false) {
            $container->prependExtensionConfig('framework', ['rate_limiter' => ['nexum_security_mfa_verify' => ['policy' => 'sliding_window', 'limit' => 10, 'interval' => '15 minutes']]]);
        }
        if ($raw['security']['passkeys']['enabled'] ?? false) {
            $container->prependExtensionConfig('framework', ['rate_limiter' => ['nexum_security_passkey_auth' => ['policy' => 'sliding_window', 'limit' => 20, 'interval' => '15 minutes']]]);
        }
        if (($raw['doctrine']['enabled'] ?? true) && $container->hasExtension('doctrine')) {
            $this->prependDoctrine($container, $raw);
        }
    }

    /** @param array<array-key, array<string, mixed>> $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        /** @var array<string, mixed> $config */
        $config = $container->resolveEnvPlaceholders($container->getParameterBag()->resolveValue($config), true);
        $discovery = $this->discovery ?? (new ArchitectureDiscovery())->discover((new DiscoveryOptionsResolver())->resolve($container, $configs));
        $architecture = (new ArchitectureCompiler())->compile($discovery, $config);
        $this->validateFeatures($container, $architecture, $config);
        $this->registerArchitecture($container, $architecture, $config);
        $this->loadApplicationServices($container, $architecture);
        $this->loadNexumServices($container, $config);
    }

    /**
     * @param array<array-key, array<string, mixed>> $configs
     *
     * @return array<string, mixed>
     */
    private function mergeRaw(array $configs): array
    {
        $merged = [];
        foreach ($configs as $config) {
            $merged = array_replace_recursive($merged, $config);
        }

        return $merged;
    }

    /** @param array<string, mixed> $raw */
    private function prependDoctrine(ContainerBuilder $container, array $raw): void
    {
        $mappings = [
            'NexumEntity' => $this->mapping(\dirname((string) (new \ReflectionClass(AbstractEntity::class))->getFileName()), 'Nexum\\Entity', 'NexumEntity'),
        ];
        if (null === $this->discovery) {
            throw new \LogicException('Nexum discovery must run before Doctrine mappings are prepended.');
        }
        foreach ($this->discovery->modules as $module) {
            foreach ($module->entityDirectories as $index => $directory) {
                $relative = trim(substr($directory, \strlen($module->directory)), '/');
                $prefix = $module->namespace.('' === $relative ? '' : '\\'.str_replace('/', '\\', $relative));
                $mappings['NexumApp'.substr(hash('xxh128', $directory), 0, 12)] = $this->mapping($directory, $prefix, 'NexumApp'.$index);
            }
        }
        foreach ($this->discovery->projects as $project) {
            foreach ($project->entityDirectories as $index => $directory) {
                $relative = trim(substr($directory, \strlen($project->directory)), '/');
                $prefix = $project->namespace.('' === $relative ? '' : '\\'.str_replace('/', '\\', $relative));
                $mappings['NexumProject'.substr(hash('xxh128', $directory), 0, 12)] = $this->mapping($directory, $prefix, 'NexumProject'.$index);
            }
        }
        $resolveTargets = [];
        if ($raw['user']['enabled'] ?? false) {
            $entityClass = $raw['user']['entity_class'] ?? 'App\\Entity\\User';
            $resolveTargets[SubjectUserInterface::class] = $entityClass;
            $mappings['NexumUser'] = $this->mapping(\dirname((string) (new \ReflectionClass(ApiAccessToken::class))->getFileName()), 'Nexum\\User\\Entity', 'NexumUser');
        }
        if ($raw['security']['mfa']['enabled'] ?? false) {
            $mappings['NexumSecurityMfa'] = $this->mapping(\dirname(__DIR__).'/Security/Mfa/Entity', 'Nexum\\Security\\Mfa\\Entity', 'NexumSecurityMfa');
        }
        if ($raw['security']['passkeys']['enabled'] ?? false) {
            $mappings['NexumSecurityPasskey'] = $this->mapping(\dirname(__DIR__).'/Security/Passkey/Entity', 'Nexum\\Security\\Passkey\\Entity', 'NexumSecurityPasskey');
        }
        $container->prependExtensionConfig('doctrine', ['orm' => [
            'resolve_target_entities' => $resolveTargets,
            'mappings' => $mappings,
            'filters' => ['nexum_soft_delete' => ['class' => SoftDeleteFilter::class, 'enabled' => true]],
        ]]);
    }

    /** @return array<string, mixed> */
    private function mapping(string $directory, string $prefix, string $alias): array
    {
        return ['type' => 'attribute', 'is_bundle' => false, 'dir' => $directory, 'prefix' => $prefix, 'alias' => $alias];
    }

    /** @param array<string, mixed> $config */
    private function validateFeatures(ContainerBuilder $container, CompiledArchitecture $architecture, array $config): void
    {
        if ($config['doctrine']['enabled'] && !$this->hasBundle($container, 'Doctrine\\Bundle\\DoctrineBundle\\DoctrineBundle')) {
            throw new \LogicException('nexum.doctrine is enabled, but DoctrineBundle is not registered.');
        }
        if ($config['api']['enabled'] && !$config['doctrine']['enabled']) {
            throw new \LogicException('nexum.api requires nexum.doctrine.');
        }
        if ($config['user']['enabled'] && (!$config['doctrine']['enabled'] || !$config['security']['enabled'])) {
            throw new \LogicException('nexum.user requires nexum.doctrine and nexum.security.');
        }
        if (($config['security']['mfa']['enabled'] || $config['security']['passkeys']['enabled']) && (!$config['security']['enabled'] || !$config['doctrine']['enabled'])) {
            throw new \LogicException('Nexum MFA and passkeys require nexum.security and nexum.doctrine.');
        }
        foreach ([
            'security.api' => $config['security']['api'],
            'security.mfa.api' => $config['security']['mfa']['api'],
            'security.passkeys.api' => $config['security']['passkeys']['api'],
            'management.api' => $config['management']['api'],
        ] as $name => $api) {
            if ($api['enabled']) {
                $this->validateApiProjects($architecture, $api['projects'], $name);
            }
        }
        if ($config['security']['api']['enabled'] && !$config['security']['enabled']) {
            throw new \LogicException('nexum.security.api requires nexum.security.');
        }
        if ($config['security']['mfa']['api']['enabled'] && !$config['security']['mfa']['enabled']) {
            throw new \LogicException('nexum.security.mfa.api requires nexum.security.mfa.');
        }
        if ($config['security']['passkeys']['api']['enabled'] && !$config['security']['passkeys']['enabled']) {
            throw new \LogicException('nexum.security.passkeys.api requires nexum.security.passkeys.');
        }
        if ($config['management']['api']['enabled'] && (!$config['management']['enabled'] || !$config['api']['enabled'] || !$config['security']['enabled'])) {
            throw new \LogicException('nexum.management.api requires management, api and security.');
        }
        if ($config['security']['passkeys']['enabled']) {
            $this->validateRelyingParties($architecture, $config['security']['passkeys']);
        }
    }

    private function hasBundle(ContainerBuilder $container, string $class): bool
    {
        $bundles = $container->hasParameter('kernel.bundles') ? $container->getParameter('kernel.bundles') : [];

        return \is_array($bundles) && \in_array($class, $bundles, true);
    }

    /** @param list<string> $projects */
    private function validateApiProjects(CompiledArchitecture $architecture, array $projects, string $name): void
    {
        if (!$architecture->projectMode()) {
            return;
        }
        if ([] === $projects) {
            throw new \LogicException(\sprintf('nexum.%s requires an explicit projects list or ["*"] in Project Mode.', $name));
        }
        if (['*'] === $projects) {
            return;
        }
        $known = array_map(static fn ($project): string => $project->id, $architecture->projects);
        foreach ($projects as $project) {
            if (!\in_array($project, $known, true)) {
                throw new \LogicException(\sprintf('nexum.%s references unknown project "%s".', $name, $project));
            }
        }
    }

    /** @param array<string, mixed> $config */
    private function validateRelyingParties(CompiledArchitecture $architecture, array $config): void
    {
        if (!$architecture->projectMode()) {
            if (empty($config['relying_party']['rp_id']) || [] === $config['relying_party']['allowed_origins']) {
                throw new \LogicException('Nexum passkeys require security.passkeys.relying_party in Application Mode.');
            }

            return;
        }
        $apiProjects = $this->selectedProjectIds($architecture, $config['api']['projects']);
        foreach ($apiProjects as $projectId) {
            if (!isset($config['relying_parties'][$projectId])) {
                throw new \LogicException(\sprintf('Nexum passkey API project "%s" needs a relying party.', $projectId));
            }
        }
    }

    /**
     * @param list<string> $selected
     *
     * @return list<string>
     */
    private function selectedProjectIds(CompiledArchitecture $architecture, array $selected): array
    {
        if (!$architecture->projectMode()) {
            return [];
        }
        if (['*'] === $selected) {
            return array_values(array_map(static fn ($project): string => $project->id, $architecture->projects));
        }

        return $selected;
    }

    /** @param array<string, mixed> $config */
    private function registerArchitecture(ContainerBuilder $container, CompiledArchitecture $architecture, array $config): void
    {
        $moduleData = array_map(static fn ($module): array => get_object_vars($module), $architecture->modules);
        $projectData = array_map(static fn ($project): array => get_object_vars($project), $architecture->projects);
        $container->register('nexum.module_registry', ModuleRegistry::class)->setArguments([$moduleData, $architecture->applicationModules, $architecture->projectModules]);
        $container->setAlias(ModuleRegistryInterface::class, 'nexum.module_registry')->setPublic(true);
        $container->register('nexum.project_registry', ProjectRegistry::class)->setArguments([$projectData]);
        $container->setAlias(ProjectRegistryInterface::class, 'nexum.project_registry')->setPublic(true);
        $container->autowire(ProjectContext::class)->setArguments([new Reference('request_stack'), new Reference(ProjectRegistryInterface::class), new Reference(ModuleRegistryInterface::class)]);
        $container->setAlias(ProjectContextInterface::class, ProjectContext::class)->setPublic(true);
        $container->register(NexumRouteLoader::class)->setArguments([
            new Reference('routing.loader.attribute.directory'),
            new Reference('routing.loader.php'),
            new Reference(ModuleRegistryInterface::class),
            new Reference(ProjectRegistryInterface::class),
            $architecture->applicationModules,
            $architecture->projectModules,
            $config,
            $this->projectDir($container),
        ])->addTag('routing.loader', ['priority' => 100]);
    }

    private function projectDir(ContainerBuilder $container): string
    {
        $projectDir = $container->getParameter('kernel.project_dir');
        if (!\is_string($projectDir)) {
            throw new \LogicException('The kernel.project_dir parameter must be a string.');
        }

        return $projectDir;
    }

    private function loadApplicationServices(ContainerBuilder $container, CompiledArchitecture $architecture): void
    {
        $loader = new ArchitectureServiceLoader();
        foreach ($architecture->projects as $project) {
            $loader->load($container, $project->directory, $project->namespace, $project->descriptorFile, true);
        }
        foreach ($architecture->usedModules() as $class) {
            $module = $architecture->modules[$class];
            $loader->load($container, $module->directory, $module->namespace, $module->descriptorFile);
        }
    }

    /** @param array<string, mixed> $config */
    private function loadNexumServices(ContainerBuilder $container, array $config): void
    {
        $yaml = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__)));
        foreach (['Http/config/services.yaml', 'Serializer/config/services.yaml', 'Validator/config/services.yaml'] as $file) {
            $yaml->load($file);
        }
        if ($config['doctrine']['enabled']) {
            $yaml->load('Doctrine/config/services.yaml');
        }
        if ($config['api']['enabled']) {
            $yaml->load('Api/config/services.yaml');
        }
        if ($config['security']['enabled']) {
            $container->setParameter('nexum.security.access_token_ttl', $config['security']['access_token_ttl']);
            $container->setParameter('nexum.security.role_permissions', $config['security']['role_permissions']);
            $yaml->load('Security/config/services.yaml');
        }
        if ($config['user']['enabled']) {
            $container->setParameter('nexum.user.entity_class', $config['user']['entity_class']);
            $yaml->load('User/config/services.yaml');
        }
        if ($config['security']['mfa']['enabled']) {
            $container->setParameter('nexum.security_mfa.issuer', $config['security']['mfa']['issuer']);
            $container->setParameter('nexum.security_mfa.challenge_ttl', $config['security']['mfa']['challenge_ttl']);
            $container->setParameter('nexum.security_mfa.max_attempts', $config['security']['mfa']['max_attempts']);
            $yaml->load('Security/Mfa/config/services.yaml');
        }
        if ($config['security']['passkeys']['enabled']) {
            $rp = $config['security']['passkeys']['relying_party'];
            $container->setParameter('nexum.security_passkey.relying_party', $rp);
            $container->setParameter('nexum.security_passkey.relying_parties', $config['security']['passkeys']['relying_parties']);
            $container->setParameter('nexum.security_passkey.challenge_ttl', $config['security']['passkeys']['challenge_ttl']);
            $container->setParameter('nexum.security_passkey.max_attempts', $config['security']['passkeys']['max_attempts']);
            $yaml->load('Security/Passkey/config/services.yaml');
        }
        if ($config['filesystem']['enabled']) {
            $container->register(LocalStorage::class)->setArguments([$config['filesystem']['root'], new Reference('filesystem'), $config['filesystem']['max_read_bytes']]);
            $container->setAlias(StorageInterface::class, LocalStorage::class);
        }
        if ($config['mailer']['enabled']) {
            $container->register(SymfonyMailer::class)->setArguments([new Reference('mailer'), $config['mailer']['default_from']]);
            $container->setAlias(MailerInterface::class, SymfonyMailer::class);
        }
        if ($config['messenger']['enabled']) {
            $yaml->load('Messenger/config/services.yaml');
        }
        if ($config['qrcode']['enabled']) {
            $container->autowire(EndroidQrCodeGenerator::class);
            $container->setAlias(QrCodeGeneratorInterface::class, EndroidQrCodeGenerator::class);
        }
        if ($config['management']['enabled']) {
            $yaml->load('Management/config/services.yaml');
        }
        $container->autowire(KernelInformation::class)->setArguments([new Reference('kernel'), new Reference(ModuleRegistryInterface::class)]);
        $container->setAlias(KernelInformationInterface::class, KernelInformation::class);
        $container->autowire(AboutCommand::class)->setAutoconfigured(true);
    }
}
