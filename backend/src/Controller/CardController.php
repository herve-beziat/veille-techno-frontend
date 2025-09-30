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
            new OA\Response(response: 403, description: "Non autorisé"),
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
        if ($boardList->getOwner() !== $user) {
            return $this->json(['error' => 'Non autorisé'], 403);
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
            'position' => $card->getPosition()
        ], 201);
    }

    #[Route('/{id}', name: 'api_cards_update', methods: ['PUT'])]
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
            new OA\Response(response: 403, description: "Non autorisé"),
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
        if ($card->getList()->getOwner() !== $user) {
            return $this->json(['error' => 'Non autorisé'], 403);
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
            if ($newList && $newList->getOwner() === $user) {
                $card->setList($newList);
            }
        }

        $card->setUpdatedAt(new \DateTime());

        $em->flush();

        return $this->json(['message' => 'Carte mise à jour']);
    }

    #[Route('/{id}', name: 'api_cards_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/cards/{id}",
        summary: "Supprime une carte (si elle appartient au user connecté)",
        tags: ["Card"],
        responses: [
            new OA\Response(response: 200, description: "Carte supprimée"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Non autorisé"),
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
        if ($card->getList()->getOwner() !== $user) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }

        $em->remove($card);
        $em->flush();

        return $this->json(['message' => 'Carte supprimée']);
    }
}
