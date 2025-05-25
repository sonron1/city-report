<?php

namespace App\Entity;

use App\Repository\ReparationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReparationRepository::class)]
class Reparation
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  private ?int $id = null;

  #[ORM\Column(type: 'text')]
  private ?string $description = null;

  #[ORM\Column(type: Types::DATETIME_MUTABLE)]
  private ?\DateTimeInterface $dateDebut = null;

  #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
  private ?\DateTimeInterface $dateFin = null;

  #[ORM\Column(length: 50)]
  private ?string $statut = 'planifiée';

  #[ORM\OneToOne(inversedBy: 'reparation', cascade: ['persist', 'remove'])]
  #[ORM\JoinColumn(nullable: false)]
  private ?Signalement $signalement = null;

  #[ORM\ManyToOne(inversedBy: 'reparations')]
  #[ORM\JoinColumn(nullable: false)]
  private ?Utilisateur $utilisateur = null;

  // Getters et setters...
}