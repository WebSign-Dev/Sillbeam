<?php

declare(strict_types=1);

namespace Sillbeam\Architecture;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use Sillbeam\Module\Attribute\SillbeamModule;
use Sillbeam\Project\Attribute\SillbeamProject;

final class SourceFileParser
{
    public function parse(string $path): SourceFile
    {
        $nodes = (new ParserFactory())->createForNewestSupportedVersion()->parse((string) file_get_contents($path)) ?? [];
        $collector = new class extends NodeVisitorAbstract {
            /** @var list<class-string> */
            public array $classes = [];
            /** @var array<class-string, string> */
            public array $descriptors = [];
            /** @var list<array{class: string, line: int}> */
            public array $references = [];

            public function enterNode(Node $node): ?int
            {
                if ($node instanceof Node\Stmt\Use_ || $node instanceof Node\Stmt\GroupUse) {
                    return NodeTraverser::DONT_TRAVERSE_CHILDREN;
                }
                if ($node instanceof Node\Stmt\ClassLike && null !== $node->name) {
                    /** @var class-string $class */
                    $class = (string) $node->namespacedName;
                    $this->classes[] = $class;
                    foreach ($node->attrGroups as $group) {
                        foreach ($group->attrs as $attribute) {
                            if (null !== ($descriptor = $this->descriptorType($attribute->name))) {
                                if (isset($this->descriptors[$class])) {
                                    throw new \LogicException('Multiple Sillbeam descriptor attributes on '.$class);
                                }
                                $this->descriptors[$class] = $descriptor;
                            }
                        }
                    }
                }
                if ($node instanceof Node\Stmt\ClassLike) {
                    foreach ((array) ($node->implements ?? []) as $type) {
                        $this->type($type);
                    }
                    $extends = $node->extends ?? null;
                    if (\is_array($extends)) {
                        foreach ($extends as $type) {
                            $this->type($type);
                        }
                    } else {
                        $this->type($extends);
                    }
                }
                if ($node instanceof Node\Param || $node instanceof Node\Stmt\Property || $node instanceof Node\Stmt\ClassConst) {
                    $this->type($node->type);
                }
                if ($node instanceof Node\FunctionLike) {
                    $this->type($node->getReturnType());
                }
                if ($node instanceof Node\Expr\New_ || $node instanceof Node\Expr\StaticCall || $node instanceof Node\Expr\StaticPropertyFetch || $node instanceof Node\Expr\ClassConstFetch || $node instanceof Node\Expr\Instanceof_) {
                    $this->type($node->class);
                }
                if ($node instanceof Node\Attribute) {
                    if (null !== $this->descriptorType($node->name)) {
                        return NodeTraverser::DONT_TRAVERSE_CHILDREN;
                    }
                    $this->type($node->name);
                }
                if ($node instanceof Node\Stmt\Catch_) {
                    foreach ($node->types as $type) {
                        $this->type($type);
                    }
                }
                if ($node instanceof Node\Stmt\TraitUse) {
                    foreach ($node->traits as $type) {
                        $this->type($type);
                    }
                }

                return null;
            }

            private function descriptorType(Node\Name $name): ?string
            {
                foreach ([SillbeamModule::class, SillbeamProject::class] as $attribute) {
                    if (0 === strcasecmp((string) $name, $attribute)) {
                        return $attribute;
                    }
                }

                return null;
            }

            private function type(?Node $type): void
            {
                if ($type instanceof Node\Name) {
                    if (!\in_array(strtolower((string) $type), ['self', 'static', 'parent'], true)) {
                        $this->references[] = ['class' => (string) $type, 'line' => $type->getStartLine()];
                    }
                } elseif ($type instanceof Node\NullableType) {
                    $this->type($type->type);
                } elseif ($type instanceof Node\UnionType || $type instanceof Node\IntersectionType) {
                    foreach ($type->types as $member) {
                        $this->type($member);
                    }
                }
            }
        };
        (new NodeTraverser(new NameResolver(), $collector))->traverse($nodes);

        return new SourceFile($path, $collector->classes, $collector->descriptors, $collector->references);
    }
}
