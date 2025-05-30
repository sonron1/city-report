<?php

namespace App\Form;

use App\Entity\Signalement;
use App\Entity\Categorie;
use App\Entity\Ville;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class SignalementTypeForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Titre de votre signalement']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'rows' => 5, 'placeholder' => 'Décrivez le problème en détail']
            ])
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'nom',
                'label' => 'Catégorie',
                'attr' => ['class' => 'form-select']
            ])
            ->add('ville', EntityType::class, [
                'class' => Ville::class,
                'choice_label' => 'nom',
                'label' => 'Ville',
                'attr' => ['class' => 'form-select', 'onchange' => 'centerMapOnCity(this.value)']
            ])


            ->add('arrondissement', EntityType::class, [
                'class' => Arrondissement::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionnez un arrondissement (optionnel)',
                'required' => false,
                'label' => 'Arrondissement',
                'query_builder' => function (ArrondissementRepository $er) use ($options) {
                  $builder = $er->createQueryBuilder('a')
                      ->orderBy('a.nom', 'ASC');

                  // Si une ville est déjà sélectionnée, filtrer les arrondissements par ville
                  if ($options['data'] && $options['data']->getVille()) {
                    $builder->where('a.ville = :ville')
                        ->setParameter('ville', $options['data']->getVille());
                  }

                  return $builder;
                },
            ])

            ->add('photo', FileType::class, [
                'label' => 'Photo',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG ou PNG)',
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            // Ajoutez ces champs pour les coordonnées
            ->add('latitude', HiddenType::class, [
                'required' => true,
            ])
            ->add('longitude', HiddenType::class, [
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Signalement::class,
        ]);
    }
}