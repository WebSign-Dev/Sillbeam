<?php

declare(strict_types=1);

namespace Nexum\User\Security;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/** @implements UserProviderInterface<UserInterface> */
final readonly class DoctrineUserProvider implements UserProviderInterface
{
    /** @param class-string<UserInterface> $userClass */
    public function __construct(
        private ManagerRegistry $registry,
        private string $userClass,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->manager()->getRepository($this->userClass)->findOneBy([
            'email' => strtolower(trim($identifier)),
        ]);

        if (!$user instanceof UserInterface) {
            $exception = new UserNotFoundException();
            $exception->setUserIdentifier($identifier);

            throw $exception;
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$this->supportsClass($user::class)) {
            throw new UnsupportedUserException(\sprintf('Unsupported user class "%s".', $user::class));
        }

        $manager = $this->manager();
        $identifier = $manager->getClassMetadata($this->userClass)->getIdentifierValues($user);
        $refreshed = $manager->find($this->userClass, $identifier);

        if (!$refreshed instanceof UserInterface) {
            $exception = new UserNotFoundException();
            $exception->setUserIdentifier($user->getUserIdentifier());

            throw $exception;
        }

        return $refreshed;
    }

    public function supportsClass(string $class): bool
    {
        return $class === $this->userClass || is_subclass_of($class, $this->userClass);
    }

    private function manager(): EntityManagerInterface
    {
        $manager = $this->registry->getManagerForClass($this->userClass);

        if (!$manager instanceof EntityManagerInterface) {
            throw new \LogicException(\sprintf('No Doctrine ORM manager found for Nexum user class "%s".', $this->userClass));
        }

        return $manager;
    }
}
