<?php

namespace App\Controller;

use App\Repository\CategorieRepository;
use App\Repository\VilleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CarteController extends AbstractController
{
    #[Route('/carte', name: 'app_carte')]
    public function index(VilleRepository $villeRepository, CategorieRepository $categorieRepository): Response
    {
        return $this->render('carte/index.html.twig', [
            'villes' => $villeRepository->findAll(),
            'categories' => $categorieRepository->findAll(),
        ]);
    }
}