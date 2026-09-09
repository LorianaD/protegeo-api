<?php

namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ContactsControllerTest extends WebTestCase
{
    public function testIndexRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/dossiers/1/contacts'
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testShowRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/dossiers/1/contacts/1'
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/dossiers/1/contacts',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode([
                'contact_category' => 'family',
                'contact_type' => 'person',
                'address' => '1 rue de test',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'PATCH',
            '/api/dossiers/1/contacts/1',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode([
                'firstname' => 'Jean',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'DELETE',
            '/api/dossiers/1/contacts/1'
        );

        $this->assertResponseStatusCodeSame(401);
    }
}