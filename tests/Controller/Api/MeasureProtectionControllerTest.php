<?php

namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MeasureProtectionControllerTest extends WebTestCase
{
    public function testIndexRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/dossiers/1/measure-protections'
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCurrentRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/dossiers/1/measure-protections/current'
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/dossiers/1/measure-protections',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode([
                'measure_type' => 'Curatelle renforcée',
                'judgment_date' => '2026-07-01',
                'start_date' => '2026-07-15',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'PATCH',
            '/api/dossiers/1/measure-protections/1',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode([
                'measure_type' => 'Tutelle',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }
}