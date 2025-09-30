<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    public function testRegisterUser(): void
    {
        $client = static::createClient();

        $email = 'user_' . uniqid() . '@example.com';
        $payload = json_encode([
            'email' => $email,
            'password' => 'secret123',
            'name' => 'Tester'
        ]);

        $client->request('POST', '/api/register', server: [
            'CONTENT_TYPE' => 'application/json'
        ], content: $payload);

        $this->assertResponseStatusCodeSame(201);

        $responseContent = $client->getResponse()->getContent();
        $this->assertStringContainsString('Inscription OK', $responseContent);
    }

    public function testLoginUser(): void
    {
        $client = static::createClient();

        // On crée d’abord un user
        $email = 'login_' . uniqid() . '@example.com';
        $client->request('POST', '/api/register', server: [
            'CONTENT_TYPE' => 'application/json'
        ], content: json_encode([
            'email' => $email,
            'password' => 'secret123',
            'name' => 'Login User'
        ]));
        $this->assertResponseStatusCodeSame(201);

        // Puis on se log
        $client->request('POST', '/api/login_check', server: [
            'CONTENT_TYPE' => 'application/json'
        ], content: json_encode([
            'email' => $email,
            'password' => 'secret123'
        ]));

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
    }

    public function testMeWithToken(): void
    {
        $client = static::createClient();

        // Création + login pour récupérer un token
        $email = 'me_' . uniqid() . '@example.com';
        $client->request('POST', '/api/register', server: [
            'CONTENT_TYPE' => 'application/json'
        ], content: json_encode([
            'email' => $email,
            'password' => 'secret123',
            'name' => 'Me Tester'
        ]));
        $this->assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/login_check', server: [
            'CONTENT_TYPE' => 'application/json'
        ], content: json_encode([
            'email' => $email,
            'password' => 'secret123'
        ]));
        $this->assertResponseIsSuccessful();

        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        // On appelle /api/me avec le token
        $client->request('GET', '/api/me', server: [
            'HTTP_Authorization' => 'Bearer ' . $token
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($email, $data['email']);
        $this->assertEquals('Me Tester', $data['name']);
    }
}