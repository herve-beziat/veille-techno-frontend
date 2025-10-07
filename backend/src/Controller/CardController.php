<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\BoardList;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/cards')]
final class CardController extends AbstractController
{
    #[Route('/all', name: 'api_cards_list_all', methods: ['GET'])]
    #[OA\Get(
        path: "/api/cards/all",
        summary: "Liste toutes les cartes quel que soit le propriétaire de la liste",
        tags: ["Card"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Tableau de toutes les cartes",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "title", type: "string", example: "Acheter du café"),
                            new OA\Property(property: "description", type: "string", nullable: true, example: "Prendre du café moulu"),
                            new OA\Property(property: "position", type: "integer", example: 1),
                            new OA\Property(property: "listId", type: "integer", example: 3),
                            new OA\Property(property: "listTitle", type: "string", example: "À faire"),
                            new OA\Property(property: "ownerId", type: "integer", nullable: true, example: 7),
                            new OA\Property(property: "createdAt", type: "string", format: "date-time", example: "2024-01-01 12:00:00"),
                            new OA\Property(property: "updatedAt", type: "string", nullable: true, format: "date-time", example: "2024-01-02 14:00:00"),
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié")
        ]
    )]
    public function listAll(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $cards = $em->getRepository(Card::class)
            ->createQueryBuilder('c')
            ->addSelect('l')
            ->join('c.list', 'l')
            ->addSelect('o')
            ->leftJoin('l.owner', 'o')
            ->orderBy('l.position', 'ASC')
            ->addOrderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();

        $data = array_map(static function (Card $card): array {
            $list = $card->getList();
            $owner = $list?->getOwner();

            return [
                'id' => $card->getId(),
                'title' => $card->getTitle(),
                'description' => $card->getDescription(),
                'position' => $card->getPosition(),
                'listId' => $list?->getId(),
                'listTitle' => $list?->getTitle(),
                'ownerId' => $owner?->getId(),
                'createdAt' => $card->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $card->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $cards);

        return $this->json($data);
    }

    #[Route('', name: 'api_cards_list', methods: ['GET'])]
    #[OA\Get(
        path: "/api/cards",
        summary: "Liste toutes les cartes d’une liste",
        tags: ["Card"],
        parameters: [
            new OA\Parameter(name: "list_id", in: "query", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Tableau des cartes"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 404, description: "Liste introuvable")
        ]
    )]
    public function list(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $listId = $request->query->get('list_id');
        $boardList = $em->getRepository(BoardList::class)->find($listId);

        if (!$boardList) {
            return $this->json(['error' => 'Liste introuvable'], 404);
        }
        if ($boardList->getOwner() !== $user) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }

        $cards = $boardList->getCards();
        $data = array_map(fn(Card $card) => [
            'id' => $card->getId(),
            'title' => $card->getTitle(),
            'description' => $card->getDescription(),
            'position' => $card->getPosition(),
            'createdAt' => $card->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $card->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ], $cards->toArray());

        return $this->json($data);
    }

    #[Route('', name: 'api_cards_create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/cards",
        summary: "Créer une nouvelle carte dans une liste",
        tags: ["Card"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "list_id", type: "integer", example: 1),
                    new OA\Property(property: "title", type: "string", example: "Acheter du café"),
                    new OA\Property(property: "description", type: "string", example: "Prendre du café moulu au supermarché")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Carte créée"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 404, description: "Liste introuvable")
        ]
    )]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $boardList = $em->getRepository(BoardList::class)->find($data['list_id'] ?? null);

        if (!$boardList) {
            return $this->json(['error' => 'Liste introuvable'], 404);
        }


        $lastPosition = $em->getRepository(Card::class)
            ->createQueryBuilder('c')
            ->select('MAX(c.position)')
            ->where('c.list = :list')
            ->setParameter('list', $boardList)
            ->getQuery()
            ->getSingleScalarResult();

        $card = new Card();
        $card->setTitle($data['title'] ?? 'Sans titre');
        $card->setDescription($data['description'] ?? '');
        $card->setList($boardList);
        $card->setPosition(($lastPosition ?? 0) + 1);
        $card->setCreatedAt(new \DateTimeImmutable());

        $em->persist($card);
        $em->flush();

        return $this->json([
            'message' => 'Carte créée',
            'id' => $card->getId(),
            'title' => $card->getTitle(),
            'description' => $card->getDescription(),
            'position' => $card->getPosition(),
            'createdAt' => $card->getCreatedAt()?->format('Y-m-d H:i:s')
        ], 201);
    }

    #[Route('/reorder', name: 'api_cards_reorder', methods: ['POST'])]
    #[OA\Post(
        path: "/api/cards/reorder",
        summary: "Réordonne plusieurs cartes et permet leur déplacement entre listes",
        tags: ["Card"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "cards",
                        type: "array",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "list_id", type: "integer", example: 2),
                                new OA\Property(property: "position", type: "integer", example: 3),
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
            new OA\Response(response: 404, description: "Carte ou liste introuvable"),
        ]
    )]

    public function reorder(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        if (!isset($payload['cards']) || !is_array($payload['cards'])) {
            return $this->json(['error' => 'Format de données invalide'], 400);
        }

        $updates = [];
        foreach ($payload['cards'] as $item) {
            if (!isset($item['id'], $item['list_id'], $item['position'])) {
                return $this->json(['error' => 'Format de données invalide'], 400);
            }

            $cardId = (int) $item['id'];
            if (isset($updates[$cardId])) {
                return $this->json(['error' => 'Ordre des cartes invalide'], 400);
            }

            $updates[$cardId] = [
                'list_id' => (int) $item['list_id'],
                'position' => (int) $item['position'],
            ];
        }

        if (empty($updates)) {
            return $this->json(['message' => 'Aucune mise à jour effectuée']);
        }

        $cardRepository = $em->getRepository(Card::class);
        $cards = $cardRepository->findBy([
            'id' => array_keys($updates),
        ]);

        if (count($cards) !== count($updates)) {
            return $this->json(['error' => 'Carte introuvable'], 404);
        }

        $listRepository = $em->getRepository(BoardList::class);
        $listsCache = [];

        foreach ($cards as $card) {
            $cardUpdate = $updates[$card->getId()];

            $listId = $cardUpdate['list_id'];
            if (!isset($listsCache[$listId])) {
                $listsCache[$listId] = $listRepository->find($listId);
            }

            $targetList = $listsCache[$listId];
            if (!$targetList) {
                return $this->json(['error' => 'Liste introuvable'], 404);
            }

            $card->setList($targetList);
            $card->setPosition($cardUpdate['position']);
            $card->setUpdatedAt(new \DateTime());
        }

        $em->flush();

        return $this->json(['message' => 'Ordre des cartes mis à jour']);
    }

    #[Route('/{id}', name: 'api_cards_update', methods: ['PUT'], requirements: ['id' => '\\d+'])]
    #[OA\Put(
        path: "/api/cards/{id}",
        summary: "Met à jour une carte (titre, description, position, ou déplacer vers une autre liste)",
        tags: ["Card"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "title", type: "string"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "position", type: "integer", example: 2),
                    new OA\Property(property: "list_id", type: "integer", example: 2)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Carte mise à jour"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 404, description: "Carte introuvable")
        ]
    )]
    #[Route('/{id}', name: 'api_cards_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    #[OA\Put(
        path: "/api/cards/{id}",
        summary: "Met à jour une carte (titre, description, position, ou déplacement vers une autre liste)",
        tags: ["Card"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "title", type: "string"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "position", type: "integer", example: 2),
                    new OA\Property(property: "list_id", type: "integer", example: 2)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Carte mise à jour"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 404, description: "Carte introuvable")
        ]
    )]


    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $card = $em->getRepository(Card::class)->find($id);
        if (!$card) {
            return $this->json(['error' => 'Carte introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $card->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $card->setDescription($data['description']);
        }
        if (isset($data['position'])) {
            $card->setPosition($data['position']);
        }
        if (isset($data['list_id'])) {
            $newList = $em->getRepository(BoardList::class)->find($data['list_id']);
            if ($newList) {
                $card->setList($newList);
            }
        }

        $card->setUpdatedAt(new \DateTime());

        $em->flush();

        return $this->json([
            'message' => 'Carte mise à jour',
            'id' => $card->getId(),
            'title' => $card->getTitle(),
            'description' => $card->getDescription(),
            'position' => $card->getPosition()
        ]);
    }


    #[Route('/{id}', name: 'api_cards_delete', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
    #[OA\Delete(
        path: "/api/cards/{id}",
        summary: "Supprime une carte (si elle appartient au user connecté)",
        tags: ["Card"],
        responses: [
            new OA\Response(response: 200, description: "Carte supprimée"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 404, description: "Carte introuvable")
        ]
    )]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $card = $em->getRepository(Card::class)->find($id);
        if (!$card) {
            return $this->json(['error' => 'Carte introuvable'], 404);
        }
        

        // ✅ Sauvegarder l'ID avant de supprimer
        $deletedId = $card->getId();

        $em->remove($card);
        $em->flush();

        return $this->json([
            'message' => 'Carte supprimée',
            'id' => $deletedId
        ]);
    }
}
