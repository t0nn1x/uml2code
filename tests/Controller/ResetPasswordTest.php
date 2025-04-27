<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResetPasswordTest extends WebTestCase
{
    public function testResetPasswordRequestFormLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/reset-password');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="reset_password_request_form[email]"]');
    }

    public function testRequestPasswordResetWithValidEmail(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        // Make sure our test user exists
        $testUser = $userRepository->findOneByEmail('test@example.com');
        if (!$testUser) {
            $this->markTestSkipped('Test user does not exist');
        }

        $crawler = $client->request('GET', '/reset-password');

        $form = $crawler->selectButton('Send password reset email')->form([
            'reset_password_request_form[email]' => 'test@example.com',
        ]);

        $client->submit($form);

        // Should redirect to check-email page
        $this->assertResponseRedirects('/reset-password/check-email');

        $client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('p', 'If an account matching your email exists, then an email was just sent that contains a link that you can use to reset your password. This link will expire in');
    }

    public function testRequestPasswordResetWithInvalidEmail(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/reset-password');

        $form = $crawler->selectButton('Send password reset email')->form([
            'reset_password_request_form[email]' => 'nonexistent@example.com',
        ]);

        $client->submit($form);

        // Even with invalid email, should redirect to check-email (for security)
        $this->assertResponseRedirects('/reset-password/check-email');
    }
}
