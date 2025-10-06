<?php

namespace App\Controller;

use App\Entity\BoardList;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/boardlists')]
final class BoardListController extends AbstractController
{
    #[Route('/reorder-all', name: 'api_boardlists_reorder_all', methods: ['POST'])]
    #[OA\Post(
        path: "/api/boardlists/reorder",
        summary: "Réordonne les listes du user connecté",
        tags: ["BoardList"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "lists",
                        type: "array",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1)
                            ]
                        )
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Ordre mis à jour"),
            new OA\Response(response: 400, description: "Format invalide"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 404, description: "Liste introuvable"),
        ]
    )]
    public function reorder(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        if (!isset($payload['lists']) || !is_array($payload['lists'])) {
            return $this->json(['error' => 'Format de données invalide'], 400);
        }

        $orderedIds = [];
        foreach ($payload['lists'] as $item) {
            if (!isset($item['id'])) {
                return $this->json(['error' => 'Format de données invalide'], 400);
            }
            $orderedIds[] = (int) $item['id'];
        }

        if (empty($orderedIds)) {
            return $this->json(['message' => 'Aucune mise à jour effectuée'], 200);
        }

        $uniqueIds = array_values(array_unique($orderedIds));
        if (count($uniqueIds) !== count($orderedIds)) {
            return $this->json(['error' => 'Ordre de liste invalide'], 400);
        }

        $listRepository = $em->getRepository(BoardList::class);
        $lists = $listRepository->findBy([
            'owner' => $user,
            'id' => $uniqueIds,
        ]);

        if (count($lists) !== count($uniqueIds)) {
            return $this->json(['error' => 'Certaines listes sont introuvables'], 404);
        }

        $listsById = [];
        foreach ($lists as $list) {
            $listsById[$list->getId()] = $list;
        }

        $position = 1;
        foreach ($orderedIds as $listId) {
            if (!isset($listsById[$listId])) {
                return $this->json(['error' => 'Ordre de liste invalide'], 400);
            }
            $listsById[$listId]->setPosition($position++);
        }

        $em->flush();

        return $this->json(['message' => 'Ordre des listes mis à jour']);
    }
    
    #[Route('', name: 'api_boardlists_list', methods: ['GET'])]
    #[OA\Get(
        path: "/api/boardlists",
        summary: "Liste toutes les listes du user connecté",
        tags: ["BoardList"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Tableau des listes du user connecté",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "title", type: "string", example: "À faire")
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié")
        ]
    )]
    public function list(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $lists = $em->getRepository(BoardList::class)->findBy(['owner' => $user]);

        $data = array_map(fn(BoardList $list) => [
            'id' => $list->getId(),
            'title' => $list->getTitle(),
            'position' => $list->getPosition(),
        ], $lists);

        return $this->json($data);
    }


    #[Route('', name: 'api_boardlists_create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/boardlists",
        summary: "Créer une nouvelle liste pour le user connecté",
        tags: ["BoardList"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "title", type: "string", example: "En cours")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Liste créée"),
            new OA\Response(response: 401, description: "Non authentifié")
        ]
    )]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['title']) || empty($data['title'])) {
            return $this->json(['error' => 'Le titre est requis'], 400);
        }

        // 🔑 Récupère la dernière position pour ce user
        $lastPosition = $em->getRepository(BoardList::class)
            ->createQueryBuilder('b')
            ->select('MAX(b.position)')
            ->where('b.owner = :owner')
            ->setParameter('owner', $user)
            ->getQuery()
            ->getSingleScalarResult();

        $boardList = new BoardList();
        $boardList->setTitle($data['title']);
        $boardList->setOwner($user);
        $boardList->setPosition(($lastPosition ?? 0) + 1); // auto-incrément de la position

        $em->persist($boardList);
        $em->flush();

        return $this->json([
            'message' => 'Liste créée',
            'id' => $boardList->getId(),
            'position' => $boardList->getPosition()
        ], 201);
    }

    #[Route('/{id}', name: 'api_boardlists_update', methods: ['PUT'], requirements: ['id' => '\\d+'])]
    #[OA\Put(
        path: "/api/boardlists/{id}",
        summary: "Met à jour une liste (uniquement si elle appartient au user connecté)",
        tags: ["BoardList"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "title", type: "string", example: "Terminé")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Liste mise à jour"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Non autorisé"),
            new OA\Response(response: 404, description: "Liste introuvable")
        ]
    )]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $boardList = $em->getRepository(BoardList::class)->find($id);
        if (!$boardList) {
            return $this->json(['error' => 'Liste introuvable'], 404);
        }
        if ($boardList->getOwner() !== $user) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title']) && !empty($data['title'])) {
            $boardList->setTitle($data['title']);
            $em->flush();
        }

        // 👉 On renvoie maintenant l’objet complet
        return $this->json([
            'id' => $boardList->getId(),
            'title' => $boardList->getTitle(),
            'position' => $boardList->getPosition()
        ]);
    }

    #[Route('/{id}', name: 'api_boardlists_delete', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
    #[OA\Delete(
        path: "/api/boardlists/{id}",
        summary: "Supprime une liste (uniquement si elle appartient au user connecté)",
        tags: ["BoardList"],
        responses: [
            new OA\Response(response: 200, description: "Liste supprimée"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Non autorisé"),
            new OA\Response(response: 404, description: "Liste introuvable")
        ]
    )]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $boardList = $em->getRepository(BoardList::class)->find($id);
        if (!$boardList) {
            return $this->json(['error' => 'Liste introuvable'], 404);
        }
        if ($boardList->getOwner() !== $user) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }

        $deletedId = $boardList->getId();

        $em->remove($boardList);
        $em->flush();

        return $this->json([
            'message' => 'Liste supprimée',
            'id' => $deletedId
        ]);
    }
}
