<?php

namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    public function testRegisterFailsWithInvalidJson(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/auth/register',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: '{invalid-json}'
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testRegisterFailsWhenRequiredDataIsMissing(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/auth/register',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode([
                'email' => 'test@example.com',
            ])
        );

        $this->assertResponseStatusCodeSame(400);
    }
}