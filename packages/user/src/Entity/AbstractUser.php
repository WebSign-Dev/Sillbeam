<?php

declare(strict_types=1);

namespace Nexum\User\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nexum\Entity\Entity\AbstractSoftDeletableEntity;
use Nexum\Security\Contract\ActiveUserInterface;
use Nexum\Security\Contract\PermissionAwareUserInterface;
use Nexum\Security\Contract\SubjectUserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\MappedSuperclass]
abstract class AbstractUser extends AbstractSoftDeletableEntity implements UserInterface, PasswordAuthenticatedUserInterface, PermissionAwareUserInterface, ActiveUserInterface, SubjectUserInterface
{
    /** @var non-empty-string */
    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    protected string $email;

    #[ORM\Column]
    #[Assert\NotBlank]
    protected string $passwordHash = '';

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    protected array $roles = [];

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    protected array $permissions = [];

    #[ORM\Column(options: ['default' => true])]
    protected bool $active = true;

    public function __construct(string $email)
    {
        parent::__construct();
        $this->setEmail($email);
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getSubjectIdentifier(): string
    {
        return $this->getId()->toRfc4122();
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $normalized = strtolower(trim($email));

        if ('' === $normalized) {
            throw new \InvalidArgumentException('The user email must not be empty.');
        }

        $this->email = $normalized;
    }

    #[Ignore]
    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(#[\SensitiveParameter] string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = array_values(array_unique([...$this->roles, 'ROLE_USER']));
        sort($roles);

        return $roles;
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): void
    {
        foreach ($roles as $role) {
            if (1 !== preg_match('/^ROLE_[A-Z0-9_]+$/', $role)) {
                throw new \InvalidArgumentException('Nexum roles must use the ROLE_NAME format.');
            }
        }

        $this->roles = array_values(array_unique($roles));
    }

    /** @return list<string> */
    public function getPermissions(): array
    {
        $permissions = array_values(array_unique($this->permissions));
        sort($permissions);

        return $permissions;
    }

    /** @param list<string> $permissions */
    public function setPermissions(array $permissions): void
    {
        foreach ($permissions as $permission) {
            if ('*' !== $permission && 1 !== preg_match('/^[a-z][a-z0-9_-]*(?:\.[a-z][a-z0-9_-]*)+$/', $permission)) {
                throw new \InvalidArgumentException('Nexum permissions must use a dotted lower-case name.');
            }
        }

        $this->permissions = array_values(array_unique($permissions));
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function activate(): void
    {
        $this->active = true;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    #[\Deprecated('Since Symfony 7.3, erase credentials using __serialize() instead.')]
    public function eraseCredentials(): void
    {
    }
}
