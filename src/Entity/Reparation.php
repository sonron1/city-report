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

  public function getId(): ?int
  {
      return $this->id;
  }

  public function getDescription(): ?string
  {
      return $this->description;
  }

  public function setDescription(string $description): static
  {
      $this->description = $description;

      return $this;
  }

  public function getDateDebut(): ?\DateTime
  {
      return $this->dateDebut;
  }

  public function setDateDebut(\DateTime $dateDebut): static
  {
      $this->dateDebut = $dateDebut;

      return $this;
  }

  public function getDateFin(): ?\DateTime
  {
      return $this->dateFin;
  }

  public function setDateFin(?\DateTime $dateFin): static
  {
      $this->dateFin = $dateFin;

      return $this;
  }

  public function getStatut(): ?string
  {
      return $this->statut;
  }

  public function setStatut(string $statut): static
  {
      $this->statut = $statut;

      return $this;
  }

  public function getSignalement(): ?Signalement
  {
      return $this->signalement;
  }

  public function setSignalement(Signalement $signalement): static
  {
      $this->signalement = $signalement;

      return $this;
  }

  public function getUtilisateur(): ?Utilisateur
  {
      return $this->utilisateur;
  }

  public function setUtilisateur(?Utilisateur $utilisateur): static
  {
      $this->utilisateur = $utilisateur;

      return $this;
  }
}