<?php

namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DossierControllerTest extends WebTestCase
{
    public function testIndexRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/dossiers'
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/dossiers',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode([
                'protected_person' => [],
                'measure_protection' => [],
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testShowRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/dossiers/1'
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testEditRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'PATCH',
            '/api/dossiers/1',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode([
                'reference_number' => 'TEST-001',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAddCurrentUserRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/dossiers/1/users',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode([
                'role_type' => 'curator',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testShowByReferenceRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/dossiers/reference/TEST-001'
        );

        $this->assertResponseStatusCodeSame(401);
    }
}