<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create an admin user',
)]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Admin email address')
            ->addArgument('password', InputArgument::REQUIRED, 'Admin password')
            ->addOption('first-name', null, InputOption::VALUE_OPTIONAL, 'First name')
            ->addOption('last-name', null, InputOption::VALUE_OPTIONAL, 'Last name')
            ->addOption('super-admin', null, InputOption::VALUE_NONE, 'Create super admin (ROLE_SUPER_ADMIN)')
            ->setHelp('This command allows you to create an admin user...');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $firstName = $input->getOption('first-name');
        $lastName = $input->getOption('last-name');
        $isSuperAdmin = $input->getOption('super-admin');

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->error(sprintf('User with email "%s" already exists!', $email));
            return Command::FAILURE;
        }

        try {
            $user = new User();
            $user->setEmail($email);
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setIsVerified(true);
            
            // Set roles
            $roles = ['ROLE_ADMIN'];
            if ($isSuperAdmin) {
                $roles[] = 'ROLE_SUPER_ADMIN';
            }
            $user->setRoles($roles);
            
            // Hash password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
            
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $roleText = $isSuperAdmin ? 'Super Admin' : 'Admin';
            $io->success(sprintf('%s user created successfully!', $roleText));
            $io->table(
                ['Field', 'Value'],
                [
                    ['Email', $email],
                    ['First Name', $firstName ?: 'Not set'],
                    ['Last Name', $lastName ?: 'Not set'],
                    ['Roles', implode(', ', $roles)],
                    ['Verified', 'Yes'],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Error creating admin user: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
} 
