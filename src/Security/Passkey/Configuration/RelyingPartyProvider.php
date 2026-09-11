<?php

declare(strict_types=1);

namespace Nexum\Security\Passkey\Configuration;

use Nexum\Project\ProjectContextInterface;

final readonly class RelyingPartyProvider
{
    /**
     * @param array{rp_id: string|null, allowed_origins: list<string>}           $application
     * @param array<string, array{rp_id: string, allowed_origins: list<string>}> $projects
     */
    public function __construct(
        private ProjectContextInterface $context,
        private array $application,
        private array $projects,
    ) {
    }

    public function current(): RelyingParty
    {
        $projectId = $this->context->id();
        $configuration = null === $projectId ? $this->application : ($this->projects[$projectId] ?? null);
        if (!\is_array($configuration) || !\is_string($configuration['rp_id'] ?? null) || [] === ($configuration['allowed_origins'] ?? [])) {
            throw new \LogicException('No passkey relying party is configured for the current Nexum application or project.');
        }

        return new RelyingParty($configuration['rp_id'], $configuration['allowed_origins']);
    }
}
