<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/categories')]
final class CategoryController extends AbstractController
{
    // 🟢 ROUTE 1 : CRÉER UNE CATÉGORIE
    #[Route('', name: 'api_categories_create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/categories",
        summary: "Créer une nouvelle catégorie",
        tags: ["Category"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Objet JSON contenant le nom (obligatoire) et la couleur (optionnelle au format hexadécimal).",
            content: new OA\JsonContent(
                type: "object",
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Frontend"),
                    new OA\Property(property: "color", type: "string", example: "#4f46e5", nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Catégorie créée avec succès"),
            new OA\Response(response: 400, description: "Requête invalide"),
            new OA\Response(response: 409, description: "Une catégorie avec ce nom existe déjà"),
            new OA\Response(response: 500, description: "Erreur serveur interne"),
        ]
    )]
    public function create(Request $request, EntityManagerInterface $em, CategoryRepository $repo): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        // ⚠️ Erreur : JSON invalide
        if ($payload === null) {
            return $this->json(['error' => 'Format JSON invalide'], 400);
        }

        // ✅ Vérification des champs
        if (!isset($payload['name']) || empty(trim($payload['name']))) {
            return $this->json(['error' => 'Le champ "name" est obligatoire'], 400);
        }

        $name = trim($payload['name']);
        $color = $payload['color'] ?? null;

        // ⚠️ Doublon
        if ($repo->findOneBy(['name' => $name])) {
            return $this->json(['error' => 'Une catégorie avec ce nom existe déjà'], 409);
        }

        // ⚠️ Vérification format couleur
        if ($color && !preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            return $this->json(['error' => 'Le champ "color" doit être au format hexadécimal (#RRGGBB)'], 400);
        }

        // ✅ Création de la catégorie
        $category = new Category();
        $category->setName($name);
        $category->setColor($color);

        $em->persist($category);
        $em->flush();

        return $this->json([
            'message' => 'Catégorie créée avec succès',
            'id' => $category->getId(),
            'name' => $category->getName(),
            'color' => $category->getColor()
        ], 201);
    }

    // 🟢 ROUTE 2 : LISTER LES CATÉGORIES
    #[Route('', name: 'api_categories_list', methods: ['GET'])]
    #[OA\Get(
        path: "/api/categories",
        summary: "Lister toutes les catégories existantes",
        tags: ["Category"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Retourne le tableau des catégories existantes",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "name", type: "string", example: "Backend"),
                            new OA\Property(property: "color", type: "string", example: "#1f2937", nullable: true)
                        ]
                    )
                )
            ),
            new OA\Response(response: 404, description: "Aucune catégorie trouvée"),
            new OA\Response(response: 500, description: "Erreur serveur interne")
        ]
    )]
    public function list(CategoryRepository $repo): JsonResponse
    {
        $categories = $repo->findBy([], ['name' => 'ASC']);

        if (!$categories) {
            return $this->json(['message' => 'Aucune catégorie trouvée'], 404);
        }

        $data = array_map(fn(Category $cat) => [
            'id' => $cat->getId(),
            'name' => $cat->getName(),
            'color' => $cat->getColor()
        ], $categories);

        return $this->json($data);
    }
}
