<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TestConnexionDBController extends AbstractController
{
    #[Route('/api/test-db', name: 'test_db', methods: ['GET'])]
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
