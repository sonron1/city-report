<?php

namespace App\Controller;

use App\Repository\VilleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    #[Route('/api/villes', name: 'api_villes', methods: ['GET'])]
    public function getVilles(VilleRepository $villeRepository): JsonResponse
    {
        $villes = $villeRepository->findAll();
        $data = [];

        foreach ($villes as $ville) {
            $data[$ville->getId()] = [
                'nom' => $ville->getNom(),
                'lat' => $ville->getLatitudeCentre(),
                'lng' => $ville->getLongitudeCentre()
            ];
        }

        return new JsonResponse($data);
    }
}