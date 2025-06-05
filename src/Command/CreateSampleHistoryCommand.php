<?php

namespace App\Command;

use App\Entity\ActionHistory;
use App\Entity\User;
use App\Service\ActionHistoryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-sample-history',
    description: 'Create sample history data for testing',
)]
class CreateSampleHistoryCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ActionHistoryService $historyService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Find a user to add history for
        $user = $this->entityManager->getRepository(User::class)->findOneBy([]);

        if (!$user) {
            $io->error('No users found. Please create a user first.');
            return Command::FAILURE;
        }

        $io->note('Creating sample history for user: ' . $user->getEmail());

        // Sample files for different actions
        $sampleFiles = [
            'convert' => [
                [
                    'filename' => 'User.php',
                    'content' => '<?php

namespace App\Entity;

class User
{
    private string $name;
    private string $email;
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function setName(string $name): void
    {
        $this->name = $name;
    }
    
    public function getEmail(): string
    {
        return $this->email;
    }
    
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
}'
                ],
                [
                    'filename' => 'Order.php',
                    'content' => '<?php

namespace App\Entity;

class Order
{
    private int $id;
    private User $user;
    private float $total;
    
    public function getId(): int
    {
        return $this->id;
    }
    
    public function getUser(): User
    {
        return $this->user;
    }
    
    public function setUser(User $user): void
    {
        $this->user = $user;
    }
    
    public function getTotal(): float
    {
        return $this->total;
    }
    
    public function setTotal(float $total): void
    {
        $this->total = $total;
    }
}'
                ]
            ],
            'parse' => [
                [
                    'filename' => 'parsed_diagram.json',
                    'content' => json_encode([
                        'classes' => [
                            [
                                'name' => 'User',
                                'properties' => ['name', 'email'],
                                'methods' => ['getName', 'setName', 'getEmail', 'setEmail']
                            ],
                            [
                                'name' => 'Order',
                                'properties' => ['id', 'user', 'total'],
                                'methods' => ['getId', 'getUser', 'setUser', 'getTotal', 'setTotal']
                            ]
                        ],
                        'relationships' => [
                            ['from' => 'Order', 'to' => 'User', 'type' => 'association']
                        ]
                    ], JSON_PRETTY_PRINT)
                ]
            ],
            'generate' => [
                [
                    'filename' => 'UserController.java',
                    'content' => 'package com.example.controller;

import com.example.entity.User;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/users")
public class UserController {
    
    @GetMapping("/{id}")
    public User getUser(@PathVariable Long id) {
        // Implementation here
        return null;
    }
    
    @PostMapping
    public User createUser(@RequestBody User user) {
        // Implementation here
        return user;
    }
    
    @PutMapping("/{id}")
    public User updateUser(@PathVariable Long id, @RequestBody User user) {
        // Implementation here
        return user;
    }
    
    @DeleteMapping("/{id}")
    public void deleteUser(@PathVariable Long id) {
        // Implementation here
    }
}'
                ]
            ]
        ];

        $actions = ['convert', 'parse', 'generate'];
        $count = 0;

        // Create several sample entries for each action
        foreach ($actions as $action) {
            for ($i = 0; $i < 3; $i++) {
                try {
                    $this->historyService->record(
                        $user,
                        $action,
                        $sampleFiles[$action],
                        'ClassDiagram'
                    );
                    $count++;
                    $io->text("Created $action history entry " . ($i + 1));
                } catch (\Exception $e) {
                    $io->error("Failed to create $action history entry: " . $e->getMessage());
                }
            }
        }

        $io->success("Created $count sample history entries for user: " . $user->getEmail());

        return Command::SUCCESS;
    }
} 
