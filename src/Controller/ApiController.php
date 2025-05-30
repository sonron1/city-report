<?php

namespace App\Controller;

use App\Entity\Ville;
use App\Repository\ArrondissementRepository;
use App\Repository\VilleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
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


    //Pour arrondissement
  #[Route('/arrondissements-by-ville/{id}', name: 'app_api_arrondissements_by_ville')]
  public function getArrondissementsByVille(
      Ville $ville,
      ArrondissementRepository $arrondissementRepository
  ): JsonResponse
  {
    $arrondissements = $arrondissementRepository->findBy(['ville' => $ville], ['nom' => 'ASC']);

    $data = [];
    foreach ($arrondissements as $arrondissement) {
      $data[] = [
          'id' => $arrondissement->getId(),
          'nom' => $arrondissement->getNom(),
      ];
    }

    return new JsonResponse($data);
  }

}