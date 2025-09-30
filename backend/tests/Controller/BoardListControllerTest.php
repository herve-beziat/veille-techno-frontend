<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BoardListControllerTest extends WebTestCase
{
    private function registerAndLogin(): array
    {
        $client = static::createClient();
        $email = 'bl_' . uniqid() . '@example.com';

        // register
        $client->request('POST', '/api/register', server: [
            'CONTENT_TYPE' => 'application/json'
        ], content: json_encode([
            'email' => $email,
            'password' => 'secret123',
            'name' => 'Board Lister'
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

    public function testCreateBoardList(): void
    {
        ['client' => $client, 'token' => $token] = $this->registerAndLogin();

        $client->request('POST', '/api/boardlists', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token
        ], content: json_encode([
            'title' => 'À faire'
        ]));

        $this->assertResponseStatusCodeSame(201);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Liste créée', $data['message']);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('position', $data);
    }

    public function testUpdateBoardList(): void
    {
        ['client' => $client, 'token' => $token] = $this->registerAndLogin();

        // 1) Créer une liste
        $client->request('POST', '/api/boardlists', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token
        ], content: json_encode(['title' => 'Ancien titre']));
        $this->assertResponseStatusCodeSame(201);

        $data = json_decode($client->getResponse()->getContent(), true);
        $listId = $data['id'];

        // 2) Modifier le titre de la liste
        $client->request('PUT', '/api/boardlists/' . $listId, server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token
        ], content: json_encode(['title' => 'Nouveau titre']));

        $this->assertResponseIsSuccessful();

        $updated = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Nouveau titre', $updated['title']);
    }

    public function testDeleteBoardList(): void
    {
        ['client' => $client, 'token' => $token] = $this->registerAndLogin();

        // 1) Créer une liste
        $client->request('POST', '/api/boardlists', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token
        ], content: json_encode(['title' => 'À supprimer']));
        $this->assertResponseStatusCodeSame(201);

        $data = json_decode($client->getResponse()->getContent(), true);
        $listId = $data['id'];

        // 2) Supprimer cette liste
        $client->request('DELETE', '/api/boardlists/' . $listId, server: [
            'HTTP_Authorization' => 'Bearer ' . $token
        ]);
        $this->assertResponseIsSuccessful();

        $deleted = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Liste supprimée', $deleted['message']);
        $this->assertEquals($listId, $deleted['id']);
    }
}