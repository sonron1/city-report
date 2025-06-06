<?php

namespace App\Entity;

use App\Enum\StatutReparation;
use App\Repository\ReparationRepository;
use App\Validator\ReparationValidator;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReparationRepository::class)]
#[Assert\Callback([ReparationValidator::class, 'validateDates'])]
class Reparation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ✅ Changement : DATE_MUTABLE -> DATETIME_MUTABLE
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de début est obligatoire')]
    #[Assert\GreaterThanOrEqual('today', message: 'La date de début ne peut pas être dans le passé')]
    private ?\DateTimeInterface $dateDebut = null;

    // ✅ Changement : DATE_MUTABLE -> DATETIME_MUTABLE
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    #[Assert\Length(
        min: 10,
        max: 1000,
        minMessage: 'La description doit contenir au moins {{ limit }} caractères',
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $description = null;

    #[ORM\Column(length: 50, enumType: StatutReparation::class)]
    private ?StatutReparation $statut = StatutReparation::PLANIFIEE;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le coût ne peut pas être négatif')]
    private ?float $cout = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\OneToOne(inversedBy: 'reparation', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Signalement $signalement = null;

    #[ORM\ManyToOne(inversedBy: 'reparations')]
    private ?Utilisateur $utilisateur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getStatut(): ?StatutReparation
    {
        return $this->statut;
    }

    public function setStatut(StatutReparation $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getCout(): ?float
    {
        return $this->cout;
    }

    public function setCout(?float $cout): static
    {
        $this->cout = $cout;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
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