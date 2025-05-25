<?php

namespace App\Entity;

use App\Repository\JournalValidationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JournalValidationRepository::class)]
class JournalValidation
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  private ?int $id = null;

  #[ORM\ManyToOne(inversedBy: 'journalValidations')]
  #[ORM\JoinColumn(nullable: false)]
  private ?Signalement $signalement = null;

  #[ORM\ManyToOne(inversedBy: 'journalValidations')]
  #[ORM\JoinColumn(nullable: false)]
  private ?Utilisateur $moderateur = null;

  #[ORM\Column(type: Types::DATETIME_MUTABLE)]
  private ?\DateTimeInterface $dateValidation = null;

  #[ORM\Column(length: 50)]
  private ?string $action = null;

  #[ORM\Column(type: 'text', nullable: true)]
  private ?string $commentaire = null;

  public function __construct()
  {
    $this->dateValidation = new \DateTime();
  }

  // Getters et setters...
}