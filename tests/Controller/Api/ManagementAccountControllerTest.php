<?php

namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ManagementAccountControllerTest extends WebTestCase
{
    public function testIndexRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/dossiers/1/management-accounts'
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testShowRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/dossiers/1/management-accounts/1'
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/dossiers/1/management-accounts',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode([
                'year' => 2026,
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'PATCH',
            '/api/dossiers/1/management-accounts/1',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode([
                'status' => 'validated',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }
}