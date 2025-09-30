<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

final class AuthController extends AbstractController
{
    // Route générée par défaut (page Twig)
    #[Route('/auth', name: 'app_auth')]
    public function index(): Response
    {
        return $this->render('auth/index.html.twig', [
            'controller_name' => 'AuthController',
        ]);
    }

    // Endpoint API pour inscription
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    #[OA\Post(
        path: "/api/register",
        summary: "Inscription d’un nouvel utilisateur",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "email", type: "string", example: "bob@example.com"),
                    new OA\Property(property: "password", type: "string", example: "secret456"),
                    new OA\Property(property: "name", type: "string", example: "Bob")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Utilisateur créé avec succès"),
            new OA\Response(response: 400, description: "Paramètres manquants"),
            new OA\Response(response: 409, description: "Email déjà utilisé")
        ]
    )]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'], $data['name'])) {
            return $this->json(['error' => 'email, password et name requis'], 400);
        }

        if ($em->getRepository(User::class)->findOneBy(['email' => $data['email']])) {
            return $this->json(['error' => 'Email déjà utilisé'], 409);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setName($data['name']);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($hasher->hashPassword($user, $data['password']));

        $em->persist($user);
        $em->flush();

        return $this->json(['message' => 'Inscription OK'], 201);
    }

    // Route protégée qui retourne l’utilisateur connecté
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    #[OA\Get(
        path: "/api/me",
        summary: "Retourne les informations de l’utilisateur connecté",
        tags: ["Auth"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Informations de l’utilisateur",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "email", type: "string", example: "bob@example.com"),
                        new OA\Property(property: "name", type: "string", example: "Bob"),
                        new OA\Property(property: "roles", type: "array", items: new OA\Items(type: "string"), example: ["ROLE_USER"])
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié")
        ]
    )]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
        ]);
    }
}
