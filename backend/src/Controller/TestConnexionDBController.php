<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use OpenApi\Attributes as OA;

class TestConnexionDBController extends AbstractController
{
    #[Route('/api/test-db', name: 'test_db', methods: ['GET'])]
    #[OA\Get(
    path: "/api/test-db",
    summary: "Tester la connexion à la base de données",
    tags: ["Utilitaires"],
    responses: [
        new OA\Response(
            response: 200,
            description: "Connexion réussie",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "message", type: "string", example: "Connexion OK 🚀"),
                    new OA\Property(property: "database", type: "string", example: "kanban_db")
                ]
            )
        ),
        new OA\Response(
            response: 500,
            description: "Erreur de connexion à la base de données",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "message", type: "string", example: "Erreur de connexion ❌"),
                    new OA\Property(property: "error", type: "string", example: "SQLSTATE[HY000] [1049] Unknown database 'kanban_db'")
                ]
            )
        )
    ]
)]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        try {
            $conn = $em->getConnection();
            $result = $conn->fetchAllAssociative('SELECT DATABASE() as db_name');

            return $this->json([
                'message' => 'Connexion OK',
                'database' => $result[0]['db_name'] ?? 'inconnue'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur de connexion ❌',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
