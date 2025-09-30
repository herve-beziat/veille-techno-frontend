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

#[Route('/api/users')]
final class UserController extends AbstractController
{
    #[Route('', name: 'api_users_list', methods: ['GET'])]
    #[OA\Get(
        path: "/api/users",
        summary: "Liste tous les utilisateurs (réservé aux admins)",
        tags: ["Admin"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des utilisateurs",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "email", type: "string", example: "alice@example.com"),
                            new OA\Property(property: "name", type: "string", example: "Alice"),
                            new OA\Property(property: "roles", type: "array", items: new OA\Items(type: "string"))
                        ]
                    )
                )
            ),
            new OA\Response(response: 403, description: "Accès refusé (non admin)")
        ]
    )]
    public function listUsers(EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $em->getRepository(User::class)->findAll();

        $data = array_map(function (User $user) {
            return [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'roles' => $user->getRoles(),
            ];
        }, $users);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'api_user_update', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/users/{id}",
        summary: "Met à jour un utilisateur (réservé aux admins)",
        tags: ["Admin"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "email", type: "string", example: "newmail@example.com"),
                    new OA\Property(property: "password", type: "string", example: "newpass123"),
                    new OA\Property(property: "name", type: "string", example: "Nouveau nom"),
                    new OA\Property(property: "roles", type: "array", items: new OA\Items(type: "string"))
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Utilisateur mis à jour"),
            new OA\Response(response: 400, description: "Rôle invalide"),
            new OA\Response(response: 403, description: "Accès refusé (non admin)"),
            new OA\Response(response: 404, description: "Utilisateur introuvable")
        ]
    )]
    public function updateUser(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
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
        if (isset($data['roles'])) {
            // Validation stricte des rôles
            foreach ($data['roles'] as $role) {
                if (!in_array($role, User::AVAILABLE_ROLES, true)) {
                    return $this->json(['error' => "Rôle invalide : $role"], 400);
                }
            }
            $user->setRoles($data['roles']);
        }

        $em->flush();

        return $this->json(['message' => 'Utilisateur mis à jour']);
    }

    #[Route('/{id}', name: 'api_user_show', methods: ['GET'])]
    #[OA\Get(
        path: "/api/users/{id}",
        summary: "Récupère un utilisateur par son ID (admin uniquement)",
        tags: ["Admin"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Utilisateur trouvé",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "email", type: "string", example: "alice@example.com"),
                        new OA\Property(property: "name", type: "string", example: "Alice"),
                        new OA\Property(property: "roles", type: "array", items: new OA\Items(type: "string"))
                    ]
                )
            ),
            new OA\Response(response: 403, description: "Accès refusé (non admin)"),
            new OA\Response(response: 404, description: "Utilisateur introuvable")
        ]
    )]
    public function showUser(int $id, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/{id}', name: 'api_user_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/users/{id}",
        summary: "Supprime un utilisateur par son ID (admin uniquement)",
        tags: ["Admin"],
        responses: [
            new OA\Response(response: 200, description: "Utilisateur supprimé"),
            new OA\Response(response: 403, description: "Accès refusé (non admin)"),
            new OA\Response(response: 404, description: "Utilisateur introuvable")
        ]
    )]
    public function deleteUser(int $id, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $em->remove($user);
        $em->flush();

        return $this->json(['message' => 'Utilisateur supprimé']);
    }
        
}
