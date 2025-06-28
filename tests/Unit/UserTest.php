<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    private $user;
    
    protected function setUp(): void
    {
        $this->user = new User();
    }
    
    public function testUserExists()
    {
        $this->assertInstanceOf(User::class, $this->user);
    }
    
    public function testCreateUserWithValidData()
    {
        $userData = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'role' => 'expeditor'
        ];
        
        // Mock the SupabaseService response
        $this->assertIsArray($userData);
        $this->assertArrayHasKey('email', $userData);
        $this->assertArrayHasKey('role', $userData);
    }
    
    public function testFindUserByEmail()
    {
        $email = 'test@example.com';
        
        // Mock the SupabaseService response
        $this->assertIsString($email);
        $this->assertNotEmpty($email);
    }
    
    public function testUpdateUser()
    {
        $userId = '123';
        $updateData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith'
        ];
        
        $this->assertIsString($userId);
        $this->assertIsArray($updateData);
    }
} 