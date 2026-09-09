<?php

namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BankingTransactionControllerTest extends WebTestCase
{
    public function testIndexRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/dossiers/1/banking-transactions'
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testShowRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/dossiers/1/banking-transactions/1'
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/dossiers/1/banking-transactions',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode([
                'source_bank_account_id' => 1,
                'destination_bank_account_id' => 2,
                'amount' => 100,
                'operation_date' => '2026-09-09',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'PATCH',
            '/api/dossiers/1/banking-transactions/1',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode([
                'amount' => 150,
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'DELETE',
            '/api/dossiers/1/banking-transactions/1'
        );

        $this->assertResponseStatusCodeSame(401);
    }
}