<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class TestController extends AbstractController
{
    #[Route('/api/test-reorder', name: 'api_test_reorder', methods: ['GET'])]
    public function test(): JsonResponse
    {
        return $this->json(['message' => 'Route test OK ✅']);
    }
}
