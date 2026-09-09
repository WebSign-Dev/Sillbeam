<?php

declare(strict_types=1);

namespace Nexum\User\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Nexum\User\Entity\AbstractUser;
use Nexum\User\Security\PasswordManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(name: 'nexum:user:create', description: 'Create a Nexum user with optional roles and permissions.')]
final class CreateUserCommand extends Command
{
    /** @param class-string<AbstractUser> $userClass */
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly PasswordManagerInterface $passwords,
        private readonly ValidatorInterface $validator,
        private readonly string $userClass,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Unique login email')
            ->addOption('role', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Role in ROLE_NAME format')
            ->addOption('permission', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Dotted permission name')
            ->addOption('password-stdin', null, InputOption::VALUE_NONE, 'Read the password from standard input');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');

        if (!\is_string($email) || '' === trim($email)) {
            $output->writeln('<error>A non-empty email is required.</error>');

            return Command::INVALID;
        }

        $password = $this->password($input, $output);

        if (\strlen($password) < 12) {
            $output->writeln('<error>The password must contain at least 12 characters.</error>');

            return Command::INVALID;
        }

        $roles = $input->getOption('role');
        $permissions = $input->getOption('permission');

        if (!\is_array($roles) || !\is_array($permissions)) {
            return Command::INVALID;
        }

        $user = new ($this->userClass)($email);
        $user->setRoles(array_values(array_filter($roles, 'is_string')));
        $user->setPermissions(array_values(array_filter($permissions, 'is_string')));
        $user->setPasswordHash($this->passwords->hash($user, $password));
        $violations = $this->validator->validate($user);

        if (\count($violations) > 0) {
            foreach ($violations as $violation) {
                $output->writeln(\sprintf('<error>%s: %s</error>', $violation->getPropertyPath(), $violation->getMessage()));
            }

            return Command::INVALID;
        }

        $manager = $this->registry->getManagerForClass($this->userClass);

        if (!$manager instanceof EntityManagerInterface) {
            throw new \LogicException(\sprintf('No Doctrine ORM manager found for Nexum user class "%s".', $this->userClass));
        }

        $manager->persist($user);
        $manager->flush();
        $output->writeln(\sprintf('<info>User "%s" created.</info>', $user->getUserIdentifier()));

        return Command::SUCCESS;
    }

    private function password(InputInterface $input, OutputInterface $output): string
    {
        if (true === $input->getOption('password-stdin')) {
            $password = stream_get_contents(\STDIN);

            return false === $password ? '' : rtrim($password, "\r\n");
        }

        $question = new Question('Password: ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $helper = $this->getHelper('question');

        if (!$helper instanceof QuestionHelper) {
            throw new \LogicException('The Symfony question helper is unavailable.');
        }

        $answer = $helper->ask($input, $output, $question);

        return \is_string($answer) ? $answer : '';
    }
}
