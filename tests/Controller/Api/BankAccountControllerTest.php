<?php

namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BankAccountControllerTest extends WebTestCase
{
    public function testIndexRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/dossiers/1/bank-accounts'
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testShowRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/dossiers/1/bank-accounts/1'
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/dossiers/1/bank-accounts',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode([
                'account_type' => 'current_account',
                'account_number' => '123456789237',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'PATCH',
            '/api/dossiers/1/bank-accounts/1',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode([
                'bank_name' => 'Banque Test',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }
}