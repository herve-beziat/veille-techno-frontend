<?php

namespace App\Controller;

use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class AuthDocController
{
    #[Route('/api/login_check', name: 'api_login_check_doc', methods: ['POST'])]
    #[OA\Post(
        path: "/api/login_check",
        summary: "Connexion utilisateur (JWT)",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "email", type: "string", example: "alice@example.com"),
                    new OA\Property(property: "password", type: "string", example: "secret123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Connexion réussie, JWT retourné",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "token", type: "string", example: "eyJ0eXAiOiJKV1QiLCJhbGciOi...")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Identifiants invalides")
        ]
    )]
    public function loginCheckDoc(): JsonResponse
    {
        // Cette méthode ne sera jamais exécutée : c’est juste pour la doc
        return new JsonResponse(null, 501);
    }
}
