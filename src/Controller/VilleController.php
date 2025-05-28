<?php

namespace App\Controller;

use App\Repository\VilleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VilleController extends AbstractController
{
    #[Route('/villes', name: 'app_ville')]
    public function index(VilleRepository $villeRepository): Response
    {
        return $this->render('ville/index.html.twig', [
            'villes' => $villeRepository->findAll(),
        ]);
    }

    #[Route('/ville/{id}', name: 'app_ville_show')]
    public function show(int $id, VilleRepository $villeRepository): Response
    {
        $ville = $villeRepository->find($id);
        
        if (!$ville) {
            throw $this->createNotFoundException('Ville non trouvée');
        }
        
        return $this->render('ville/show.html.twig', [
            'ville' => $ville,
            'signalements' => $ville->getSignalements(),
        ]);
    }
}