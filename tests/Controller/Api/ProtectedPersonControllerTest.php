<?php

namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProtectedPersonControllerTest extends WebTestCase
{
    public function testShowRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/dossiers/1/protected-person'
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testEditRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'PATCH',
            '/api/dossiers/1/protected-person',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode([
                'firstname' => 'Marie',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }
}