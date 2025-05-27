<?php

namespace App\Controller;

use App\Entity\Signalement;
use App\Entity\Utilisateur;
use App\Enum\StatutSignalement;
use App\Form\SignalementTypeForm;
use App\Repository\SignalementRepository;
use App\Repository\CategorieRepository;
use App\Repository\VilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

class SignalementController extends AbstractController
{
    #[Route('/signalements', name: 'app_signalements')]
    #[IsGranted('ROLE_USER')]
    public function index(SignalementRepository $signalementRepository): Response
    {
        $signalements = $signalementRepository->findBy(['etatValidation' => 'valide'], ['dateSignalement' => 'DESC']);
        
        return $this->render('signalement/index.html.twig', [
            'signalements' => $signalements,
        ]);
    }
    
    #[Route('/carte', name: 'app_carte')]
    #[IsGranted('ROLE_USER')]
    public function carte(VilleRepository $villeRepository, CategorieRepository $categorieRepository): Response
    {
        return $this->render('index.html.twig', [
            'villes' => $villeRepository->findAll(),
            'categories' => $categorieRepository->findAll(),
        ]);
    }

    #[Route('/signalement/{id}', name: 'app_signalement_show')]
    #[IsGranted('ROLE_USER')]
    public function show(int $id, SignalementRepository $signalementRepository): Response
    {
        $signalement = $signalementRepository->find($id);

        if (!$signalement) {
            throw $this->createNotFoundException('Signalement non trouvé');
        }

        return $this->render('signalement/show.html.twig', [
            'signalement' => $signalement,
        ]);
    }

    #[Route('/signalement/nouveau', name: 'app_signalement_nouveau')]
    #[IsGranted('ROLE_USER')]
    public function nouveau(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // Suppression de la ligne redondante de vérification des droits
        // car l'attribut IsGranted est déjà présent

        $signalement = new Signalement();
        $form = $this->createForm(SignalementTypeForm::class, $signalement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid('', true).'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('photos_directory'),
                        $newFilename
                    );
                    $signalement->setPhotoUrl($newFilename);
                } catch (FileException $e) {
                    // Ajout d'un message d'erreur en cas d'échec de l'upload
                    $this->addFlash('error', 'Un problème est survenu lors du téléchargement de votre photo.');
                }
            }

            $user = $this->getUser();
            if (!$user) {
                throw $this->createAccessDeniedException('Vous devez être connecté pour créer un signalement.');
            }
            
            $signalement->setUtilisateur($user);
            $signalement->setDateSignalement(new \DateTime());
            $signalement->setStatut(StatutSignalement::NOUVEAU);
            $signalement->setEtatValidation('en_attente');

            $entityManager->persist($signalement);
            $entityManager->flush();

            $this->addFlash('success', 'Votre signalement a été enregistré et sera validé prochainement.');

            return $this->redirectToRoute('app_signalements');
        }

        return $this->render('signalement/nouveau.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/mes-signalements', name: 'app_mes_signalements')]
    #[IsGranted('ROLE_USER')]
    public function mesSignalements(SignalementRepository $signalementRepository): Response
    {
        // Suppression de la vérification redondante car l'attribut IsGranted est déjà présent
        
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir vos signalements.');
        }

        return $this->render('signalement/mes_signalements.html.twig', [
            'signalements' => $signalementRepository->findBy(
                ['utilisateur' => $user],
                ['dateSignalement' => 'DESC']
            )
        ]);
    }
}