<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CardControllerTest extends WebTestCase
{
    private function registerAndLogin(): array
    {
        $client = static::createClient();
        $email = 'card_'.uniqid().'@example.com';

        // register
        $client->request('POST', '/api/register', server: [
            'CONTENT_TYPE' => 'application/json'
        ], content: json_encode([
            'email' => $email,
            'password' => 'secret123',
            'name' => 'Card Tester'
        ]));
        self::assertResponseStatusCodeSame(201);

        // login
        $client->request('POST', '/api/login_check', server: [
            'CONTENT_TYPE' => 'application/json'
        ], content: json_encode([
            'email' => $email,
            'password' => 'secret123'
        ]));
        self::assertResponseIsSuccessful();
        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        return ['client' => $client, 'token' => $token];
    }

    private function createBoardList($client, $token, $title = 'Ma liste'): int
    {
        $client->request('POST', '/api/boardlists', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer '.$token
        ], content: json_encode(['title' => $title]));
        self::assertResponseStatusCodeSame(201);

        $data = json_decode($client->getResponse()->getContent(), true);
        return $data['id'];
    }

    public function testCreateCard(): void
    {
        ['client' => $client, 'token' => $token] = $this->registerAndLogin();
        $listId = $this->createBoardList($client, $token);

        $client->request('POST', '/api/cards', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer '.$token
        ], content: json_encode([
            'list_id' => $listId,
            'title' => 'Acheter du café',
            'description' => 'Paquet moulu'
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Carte créée', $data['message']);
        $this->assertArrayHasKey('id', $data);
    }

    public function testUpdateCard(): void
    {
        ['client' => $client, 'token' => $token] = $this->registerAndLogin();
        $listId = $this->createBoardList($client, $token);

        // Créer une carte
        $client->request('POST', '/api/cards', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer '.$token
        ], content: json_encode([
            'list_id' => $listId,
            'title' => 'Ancien titre',
            'description' => 'Ancienne description'
        ]));
        $this->assertResponseStatusCodeSame(201);
        $card = json_decode($client->getResponse()->getContent(), true);
        $cardId = $card['id'];

        // Modifier la carte
        $client->request('PUT', '/api/cards/'.$cardId, server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer '.$token
        ], content: json_encode([
            'title' => 'Nouveau titre',
            'description' => 'Nouvelle description'
        ]));

        $this->assertResponseIsSuccessful();
        $updated = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Nouveau titre', $updated['title']);
        $this->assertEquals('Nouvelle description', $updated['description']);
    }

    public function testDeleteCard(): void
    {
        ['client' => $client, 'token' => $token] = $this->registerAndLogin();
        $listId = $this->createBoardList($client, $token);

        // Créer une carte
        $client->request('POST', '/api/cards', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer '.$token
        ], content: json_encode([
            'list_id' => $listId,
            'title' => 'À supprimer',
            'description' => 'Test delete'
        ]));
        $this->assertResponseStatusCodeSame(201);
        $card = json_decode($client->getResponse()->getContent(), true);
        $cardId = $card['id'];

        // Supprimer la carte
        $client->request('DELETE', '/api/cards/'.$cardId, server: [
            'HTTP_Authorization' => 'Bearer '.$token
        ]);

        $this->assertResponseIsSuccessful();
        $deleted = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Carte supprimée', $deleted['message']);
        $this->assertEquals($cardId, $deleted['id']);
    }
}