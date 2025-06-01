<?php

namespace App\Form;

use App\Entity\Reparation;
use App\Enum\StatutReparation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReparationTypeForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => StatutReparation::getChoices(),
                'required' => true
            ])
            ->add('datePlanifiee', DateType::class, [
                'label' => 'Date planifiée',
                'widget' => 'single_text',
                'required' => false
            ])
            ->add('observations', TextareaType::class, [
                'label' => 'Observations',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Observations concernant cette réparation...'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reparation::class,
        ]);
    }
}