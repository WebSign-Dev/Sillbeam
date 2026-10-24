<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture;

use Sillbeam\Core\Module\Attribute\SillbeamModule;
use Sillbeam\Core\Project\Attribute\SillbeamProject;

/** @internal Minimal descriptor creation shared by the two console commands. */
final readonly class DescriptorCreator
{
    public function __construct(private DiscoveryOptions $options)
    {
    }

    public function create(string $name, bool $project = false, ?string $owner = null): string
    {
        if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/D', $name)) {
            throw new \InvalidArgumentException('Name must start with an uppercase ASCII letter and contain only letters and digits.');
        }
        $roots = $project ? $this->options->projectRoots : $this->options->moduleRoots;
        if (null === $owner) {
            if (1 !== \count($roots)) {
                throw new \InvalidArgumentException('Configure exactly one sillbeam.discovery.'.($project ? 'projects' : 'modules').' root for creation.');
            }
            $directory = $roots[0]->path.'/'.$name;
        } else {
            $matches = [];
            foreach ((new ArchitectureDiscovery())->discover($this->options)->projects as $definition) {
                if ($owner === $definition->id || $owner === basename($definition->directory) || $owner === $definition->class) {
                    $matches[] = $definition;
                }
            }
            if (1 !== \count($matches)) {
                throw new \InvalidArgumentException('Project "'.$owner.'" must identify exactly one existing discovered project. Create or configure the project first.');
            }
            $directory = $matches[0]->directory.'/Module/'.$name;
        }
        $suffix = $project ? 'Project' : 'Module';
        $file = $directory.'/'.$name.$suffix.'.php';
        for ($path = $file; '/' !== $path && '.' !== $path; $path = \dirname($path)) {
            if (is_link($path) || preg_match('~(?:^|/)\.\.?(/|$)~', $path)) {
                throw new \InvalidArgumentException('Unsafe creation path: '.$path);
            }
            if ($this->options->excluded($path)) {
                throw new \InvalidArgumentException('Creation path is excluded from discovery: '.$path);
            }
        }
        if (file_exists($directory) || is_link($directory)) {
            throw new \InvalidArgumentException('Creation target already exists: '.$directory);
        }
        $namespace = $this->options->namespaceForDirectory($directory);
        $attribute = $project ? SillbeamProject::class : SillbeamModule::class;
        $shortAttribute = 'Sillbeam'.$suffix;
        $contents = "<?php\n\ndeclare(strict_types=1);\n\nnamespace $namespace;\n\nuse $attribute;\n\n#[$shortAttribute]\nfinal class $name$suffix\n{\n}\n";
        // Validate namespace segments (including PHP keywords) before touching the filesystem.
        try {
            (new \PhpParser\ParserFactory())->createForNewestSupportedVersion()->parse($contents);
        } catch (\PhpParser\Error $error) {
            throw new \InvalidArgumentException('Invalid descriptor name or namespace: '.$error->getMessage(), previous: $error);
        }
        $discovery = (new ArchitectureDiscovery())->discover($this->options);
        foreach ($discovery->modules as $module) {
            if ((new DiscoveryRoot($module->directory, ''))->contains($directory) || (new DiscoveryRoot($directory, ''))->contains($module->directory)) {
                throw new \InvalidArgumentException('Creation would overlap an existing module: '.$module->directory);
            }
        }
        foreach ($discovery->projects as $definition) {
            if (($project || null === $owner) && ((new DiscoveryRoot($definition->directory, ''))->contains($directory) || (new DiscoveryRoot($directory, ''))->contains($definition->directory))) {
                throw new \InvalidArgumentException('Creation would overlap an existing project: '.$definition->directory);
            }
        }
        if (!$project && null === $owner) {
            foreach ($this->options->projectRoots as $root) {
                if ($root->contains($directory)) {
                    throw new \InvalidArgumentException('Module target is inside a project root; use --project.');
                }
            }
        }
        if (!mkdir($directory, 0777, true)) {
            throw new \RuntimeException('Cannot create directory: '.$directory);
        }
        $handle = fopen($file, 'x');
        if (false === $handle) {
            throw new \RuntimeException('Cannot exclusively create descriptor: '.$file);
        }
        try {
            if (\strlen($contents) !== fwrite($handle, $contents)) {
                throw new \RuntimeException('Cannot write descriptor: '.$file);
            }
        } finally {
            fclose($handle);
        }

        return $file;
    }
}
