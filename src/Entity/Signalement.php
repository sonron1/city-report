<?php

namespace App\Entity;

use App\Enum\StatutSignalement;
use App\Repository\SignalementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SignalementRepository::class)]
class Signalement
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  private ?int $id = null;

  #[ORM\Column(length: 255)]
  #[Assert\NotBlank(message: "Le titre ne peut pas être vide")]
  #[Assert\Length(
    min: 5,
    max: 255,
    minMessage: "Le titre doit contenir au moins {{ limit }} caractères",
    maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
  )]
  private ?string $titre = null;

  #[ORM\Column(type: 'text')]
  private ?string $description = null;

  #[ORM\Column(length: 255)]
  private ?string $photoUrl = null;

  #[ORM\Column]
  private ?float $latitude = null;

  #[ORM\Column]
  private ?float $longitude = null;

  #[ORM\Column(type: Types::DATETIME_MUTABLE)]
  private ?\DateTimeInterface $dateSignalement = null;

  #[ORM\Column(length: 50, enumType: StatutSignalement::class)]
  private ?StatutSignalement $statut = StatutSignalement::NOUVEAU;

  #[ORM\Column]
  private ?int $priorite = 1;

  #[ORM\Column(length: 50)]
  private ?string $etatValidation = 'en_attente';

  #[ORM\ManyToOne(inversedBy: 'signalements')]
  #[ORM\JoinColumn(nullable: false)]
  private ?Utilisateur $utilisateur = null;

  #[ORM\ManyToOne(inversedBy: 'signalements')]
  #[ORM\JoinColumn(nullable: false)]
  private ?Categorie $categorie = null;

  #[ORM\ManyToOne(inversedBy: 'signalements')]
  #[ORM\JoinColumn(nullable: false)]
  private ?Ville $ville = null;

  #[ORM\OneToMany(mappedBy: 'signalement', targetEntity: Commentaire::class)]
  private Collection $commentaires;

  #[ORM\OneToMany(mappedBy: 'signalement', targetEntity: JournalValidation::class)]
  private Collection $journalValidations;

  #[ORM\OneToMany(mappedBy: 'signalement', targetEntity: Notification::class)]
  private Collection $notifications;

  #[ORM\OneToOne(mappedBy: 'signalement', cascade: ['persist', 'remove'])]
  private ?Reparation $reparation = null;

  #[ORM\ManyToOne(inversedBy: 'signalements')]
  private ?Cluster $cluster = null;

  public function __construct()
  {
    $this->commentaires = new ArrayCollection();
    $this->journalValidations = new ArrayCollection();
    $this->notifications = new ArrayCollection();
    $this->dateSignalement = new \DateTime();
  }

  // Getters et setters...
}