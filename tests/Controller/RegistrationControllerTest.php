<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\UserRepository;

class RegistrationControllerTest extends WebTestCase
{
    public function testRegistrationPageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Create your account');
    }

    public function testRegisterUser(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $uniqueEmail = 'new-user-' . uniqid() . '@example.com';
        $form = $crawler->selectButton('Register')->form([
            'registration_form[email]' => $uniqueEmail,
            'registration_form[firstName]' => 'New',
            'registration_form[lastName]' => 'User',
            'registration_form[plainPassword]' => 'SecurePassword123!',
            'registration_form[agreeTerms]' => true,
        ]);

        $client->submit($form);

        // Should redirect after successful registration
        $this->assertResponseRedirects('/login');

        // Follow the redirect
        $client->followRedirect();

        // Verify flash message appears
        $this->assertSelectorExists('.bg-green-50');

        // Verify user was created in database
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneByEmail($uniqueEmail);

        $this->assertNotNull($user);
        $this->assertEquals('New', $user->getFirstName());
        $this->assertEquals('User', $user->getLastName());
    }

    public function testCannotRegisterWithoutAcceptingTerms(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $uniqueEmail = 'another-user-' . uniqid() . '@example.com';
        $form = $crawler->selectButton('Register')->form([
            'registration_form[email]' => $uniqueEmail,
            'registration_form[firstName]' => 'Another',
            'registration_form[lastName]' => 'User',
            'registration_form[plainPassword]' => 'SecurePassword123!',
            'registration_form[agreeTerms]' => false,
        ]);

        $client->submit($form);

        // Should not redirect - should stay on same page with errors
        $this->assertResponseStatusCodeSame(200);

        // Should show error about terms
        $this->assertSelectorExists('.text-red-800');
    }
}
