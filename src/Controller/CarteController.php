<?php

namespace App\Controller;

use App\Repository\CategorieRepository;
use App\Repository\SignalementRepository;
use App\Repository\VilleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CarteController extends AbstractController
{
    #[Route('/carte', name: 'app_carte')]
    #[IsGranted('ROLE_USER')]
    public function index(
        VilleRepository $villeRepository, 
        CategorieRepository $categorieRepository,
        SignalementRepository $signalementRepository
    ): Response
    {
        // Récupérer uniquement les signalements validés
        $signalements = $signalementRepository->findBy(['etatValidation' => 'valide']);
        
        return $this->render('carte/index.html.twig', [
            'villes' => $villeRepository->findAll(),
            'categories' => $categorieRepository->findAll(),
            'signalements' => $signalements,
        ]);
    }
}