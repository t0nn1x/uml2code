<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityControllerTest extends WebTestCase
{
    private $client;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->userRepository = $container->get(UserRepository::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);

        // Create a test user if it doesn't exist
        $testUser = $this->userRepository->findOneByEmail('test@example.com');
        if (!$testUser) {
            $testUser = new User();
            $testUser->setEmail('test@example.com');
            $testUser->setFirstName('Test');
            $testUser->setLastName('User');
            $testUser->setIsVerified(true);

            // Hash a known password
            $hashedPassword = $this->passwordHasher->hashPassword($testUser, 'password123');
            $testUser->setPassword($hashedPassword);

            $this->userRepository->save($testUser, true);
        }
    }

    public function testLoginPageLoads(): void
    {
        $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Sign in to your account');
    }

    public function testLoginWithValidCredentials(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->client->submit($form);

        // Should redirect to dashboard after login
        $this->assertResponseRedirects('/dashboard');

        // Follow redirect
        $this->client->followRedirect();

        // Should show dashboard content
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Dashboard');
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $this->client->submit($form);

        // Should show error message
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        $this->assertSelectorExists('.bg-red-50');
    }

    public function testLogout(): void
    {
        $user = $this->userRepository->findOneByEmail('test@example.com');
        $this->client->loginUser($user);

        // Verify we're logged in by accessing the dashboard
        $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();

        // Logout
        $this->client->request('GET', '/logout');

        // After logout, accessing dashboard should redirect to login
        $this->client->request('GET', '/dashboard');
        $this->assertResponseRedirects('/login');
    }
}
