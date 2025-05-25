<?php

namespace App\Controller;

use App\Repository\SignalementRepository;
use App\Repository\VilleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(SignalementRepository $signalementRepository, VilleRepository $villeRepository): Response
    {
        // Récupérer les derniers signalements validés
        $derniersSignalements = $signalementRepository->findBy(
            ['etatValidation' => 'validé'],
            ['dateSignalement' => 'DESC'],
            5
        );
        
        // Récupérer les villes avec le plus de signalements
        $villes = $villeRepository->findAll();
        
        return $this->render('home/index.html.twig', [
            'derniers_signalements' => $derniersSignalements,
            'villes' => $villes,
        ]);
    }
}