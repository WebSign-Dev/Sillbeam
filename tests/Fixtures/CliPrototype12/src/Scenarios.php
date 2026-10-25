<?php

declare(strict_types=1);

namespace SillbeamPrototype;

/**
 * Fictional, deterministic presentation fixtures – NOT the Sillbeam analyzer.
 * "findings" = raw findings; "issues" = manually grouped user-facing diagnoses.
 */
final class Scenarios
{
    /** @return array<string, mixed> */
    public static function get(string $name): array
    {
        return match ($name) {
            'success' => [
                'modules' => 8,
                'projects' => 2,
                'findings' => 0,
                'issues' => [],
            ],
            'conflict' => [
                'modules' => 5,
                'projects' => 0,
                'findings' => 1,
                'issues' => [self::undeclaredConflict()],
            ],
            'multiple' => [
                'modules' => 12,
                'projects' => 2,
                'findings' => 11,
                'issues' => [
                    [
                        'category' => 'Dependency graph',
                        'title' => 'Circular module dependency',
                        'rule' => 'dependency.cycle',
                        'relation' => 'User → Catalog → Admin → User',
                        'summary' => 'These modules form a dependency cycle. Each module in this chain relies on the next.',
                        'caution' => 'Adding more dependencies will not remove the cycle.',
                        'steps' => [
                            'Remove the dependency edge that is not required.',
                            'If responsibilities overlap, move the shared behavior into the appropriate module.',
                        ],
                        'references' => [
                            ['file' => 'src/Module/User/UserModule.php', 'line' => 12],
                            ['file' => 'src/Module/Catalog/CatalogModule.php', 'line' => 10],
                            ['file' => 'src/Module/Admin/AdminModule.php', 'line' => 15],
                        ],
                        'findings' => 1,
                        'detail' => 'Edges: User → Catalog; Catalog → Admin; Admin → User.',
                    ],
                    [
                        'category' => 'Missing declarations',
                        'title' => 'Module accesses are not declared',
                        'rule' => 'code.undeclared_dependency',
                        'relation' => 'Billing → Catalog',
                        'summary' => 'Billing references classes owned by Catalog without declaring Catalog as a direct dependency.',
                        'caution' => 'Catalog already depends on Billing. Declaring this edge would create another cycle.',
                        'steps' => [
                            'Move the cross-module operation to its owning module.',
                            'Review the existing reverse dependency before changing the descriptor.',
                            'Extract a shared contract only if both interactions are genuinely required.',
                        ],
                        'references' => [
                            ['file' => 'src/Module/Billing/Service/InvoiceSender.php', 'line' => 24],
                            ['file' => 'src/Module/Billing/Command/ImportInvoices.php', 'line' => 47],
                            ['file' => 'src/Module/Billing/Service/InvoiceMapper.php', 'line' => 13],
                        ],
                        'findings' => 3,
                        'detail' => 'Source: App\\Module\\Billing\\BillingModule. Target: App\\Module\\Catalog\\CatalogModule.',
                    ],
                    [
                        'category' => 'Unresolved source',
                        'title' => 'Application classes cannot be resolved',
                        'rule' => 'code.unknown_class',
                        'relation' => 'Billing → App\\Missing\\InvoiceClient',
                        'summary' => 'Three references point to application classes that cannot be resolved through the configured autoload mappings.',
                        'caution' => null,
                        'steps' => [
                            'Check spelling, namespaces and use statements at each location.',
                            'Restore missing files or correct Composer PSR-4 mappings.',
                        ],
                        'references' => [
                            ['file' => 'src/Module/Billing/Service/InvoiceGateway.php', 'line' => 14],
                            ['file' => 'src/Module/Billing/Service/InvoiceGateway.php', 'line' => 29],
                            ['file' => 'src/Module/Billing/Command/InvoiceSync.php', 'line' => 9],
                        ],
                        'findings' => 3,
                        'detail' => 'The example contains unresolved application-owned symbols; it does not test third-party autoloading.',
                    ],
                    [
                        'category' => 'Activation',
                        'title' => 'A required module is disabled',
                        'rule' => 'code.inactive',
                        'relation' => 'Admin → Notification',
                        'summary' => 'Admin uses Notification although Notification is disabled in the current architecture.',
                        'caution' => null,
                        'steps' => [
                            'Enable Notification if Admin needs this feature.',
                            'Otherwise remove or relocate the access to the inactive module.',
                        ],
                        'references' => [
                            ['file' => 'src/Module/Admin/Service/AlertPublisher.php', 'line' => 27],
                            ['file' => 'src/Module/Admin/Command/NotifyUsers.php', 'line' => 19],
                        ],
                        'findings' => 2,
                        'detail' => 'Notification key: notification. Effective state: disabled.',
                    ],
                    [
                        'category' => 'Project boundary',
                        'title' => 'Private module used outside its Project',
                        'rule' => 'code.private',
                        'relation' => 'Shop → Admin / Reports',
                        'summary' => 'Code in Shop references a private module that belongs to the Admin Project.',
                        'caution' => null,
                        'steps' => [
                            'Keep Reports inside its owning Admin Project.',
                            'Move reusable contracts or behavior into a shared module if cross-Project access is required.',
                        ],
                        'references' => [
                            ['file' => 'src/Project/Shop/Controller/ExportController.php', 'line' => 32],
                            ['file' => 'src/Project/Shop/Service/ReportExport.php', 'line' => 18],
                        ],
                        'findings' => 2,
                        'detail' => 'Target ownership: project:admin:reports; caller: project:shop.',
                    ],
                ],
            ],
            default => throw new \InvalidArgumentException('Unknown scenario: '.$name.'. Choose success, conflict or multiple.'),
        };
    }

    /** @return array<string, mixed> */
    private static function undeclaredConflict(): array
    {
        return [
            'category' => 'Dependency declaration',
            'title' => 'Undeclared module access',
            'rule' => 'code.undeclared_dependency',
            'relation' => 'User → Catalog',
            'summary' => 'User uses CatalogStore from Catalog, but User does not declare Catalog as a direct dependency.',
            'caution' => 'Catalog already depends on User. Adding User → Catalog would close a dependency cycle.',
            'steps' => [
                'Move the import workflow to the module that owns this responsibility.',
                'Remove the reverse dependency if Catalog does not actually need User.',
                'Extract a shared contract if both directions are genuinely required.',
            ],
            'references' => [
                ['file' => 'src/Module/User/Command/ImportLegacyUserCommand.php', 'line' => 18],
            ],
            'findings' => 1,
            'detail' => 'Source: App\\Module\\User\\UserModule. Target: App\\Module\\Catalog\\CatalogModule. Rule: code.undeclared_dependency.',
        ];
    }
}
