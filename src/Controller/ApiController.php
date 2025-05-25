<?php

namespace App\Controller;

use App\Repository\VilleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    #[Route('/api/ville/{id}', name: 'app_api_ville', methods: ['GET'])]
    public function getVille(int $id, VilleRepository $villeRepository): JsonResponse
    {
        $ville = $villeRepository->find($id);
        
        if (!$ville) {
            return new JsonResponse(['error' => 'Ville non trouvée'], 404);
        }
        
        return new JsonResponse([
            'id' => $ville->getId(),
            'nom' => $ville->getNom(),
            'latitude' => $ville->getLatitude(),
            'longitude' => $ville->getLongitude(),
        ]);
    }
}