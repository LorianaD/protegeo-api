<?php

namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    public function testProfileRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/user/profile'
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testProfileUpdateRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'PATCH',
            '/api/user/profile',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode([
                'city' => 'Aix-en-Provence',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testPasswordUpdateRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'PATCH',
            '/api/user/password',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode([
                'current_password' => 'Test1234!1234',
                'new_password' => 'NewTest1234!1234',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }
}