<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/me')]
final class UserSelfController extends AbstractController
{
    #[Route('', name: 'api_user_self_update', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/me",
        summary: "Met à jour son propre profil",
        tags: ["User"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "email", type: "string", example: "newmail@example.com"),
                    new OA\Property(property: "password", type: "string", example: "newpass123"),
                    new OA\Property(property: "name", type: "string", example: "Nouveau nom")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Profil mis à jour"),
            new OA\Response(response: 401, description: "Non authentifié")
        ]
    )]
    public function updateSelf(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['name'])) {
            $user->setName($data['name']);
        }
        if (isset($data['password'])) {
            $user->setPassword($hasher->hashPassword($user, $data['password']));
        }

        $em->flush();

        return $this->json(['message' => 'Profil mis à jour']);
    }

    #[Route('', name: 'api_user_self_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/me",
        summary: "Supprime son propre compte",
        tags: ["User"],
        responses: [
            new OA\Response(response: 200, description: "Compte supprimé"),
            new OA\Response(response: 401, description: "Non authentifié")
        ]
    )]
    public function deleteSelf(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $em->remove($user);
        $em->flush();

        return $this->json(['message' => 'Compte supprimé']);
    }
}
