<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserCreation(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setPassword('hashed_password');
        $user->setRoles(['ROLE_USER']);

        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('test@example.com', $user->getUserIdentifier());
        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('Doe', $user->getLastName());
        $this->assertEquals('John Doe', $user->getFullName());
        $this->assertEquals('hashed_password', $user->getPassword());
        $this->assertContains('ROLE_USER', $user->getRoles());
        $this->assertFalse($user->isVerified());
    }

    public function testUserVerification(): void
    {
        $user = new User();
        $this->assertFalse($user->isVerified());

        $user->setIsVerified(true);
        $this->assertTrue($user->isVerified());
    }
}
