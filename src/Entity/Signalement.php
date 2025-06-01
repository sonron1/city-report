<?php

namespace App\Entity;

use App\Enum\EtatValidation;
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

  /**
   * État de validation du signalement
   * Valeurs possibles : en_attente, validé, rejeté
   */
  #[ORM\Column(length: 50)]
  private ?string $etatValidation = EtatValidation::EN_ATTENTE->value;

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

  // Dans src/Entity/Signalement.php
  // Ajoutez une propriété pour suivre les demandes de suppression
  #[ORM\Column(length: 20, nullable: true)]
  private ?string $demandeSuppressionStatut = null;

  #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
  private ?\DateTimeInterface $dateDemandeSuppressionStatut = null;

  // Dans Signalement.php, ajoutez cette relation
  #[ORM\ManyToOne(inversedBy: 'signalements')]
  private ?Arrondissement $arrondissement = null;

  // Et ces méthodes
  public function getArrondissement(): ?Arrondissement
  {
    return $this->arrondissement;
  }

  public function setArrondissement(?Arrondissement $arrondissement): static
  {
    $this->arrondissement = $arrondissement;
    return $this;
  }

  public function __construct()
  {
    $this->commentaires = new ArrayCollection();
    $this->journalValidations = new ArrayCollection();
    $this->notifications = new ArrayCollection();
    $this->dateSignalement = new \DateTime();
  }

  // Getters et setters...

  public function getId(): ?int
  {
      return $this->id;
  }

  public function getTitre(): ?string
  {
      return $this->titre;
  }

  public function setTitre(string $titre): static
  {
      $this->titre = $titre;

      return $this;
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

  public function getPhotoUrl(): ?string
  {
      return $this->photoUrl;
  }

  public function setPhotoUrl(string $photoUrl): static
  {
      $this->photoUrl = $photoUrl;

      return $this;
  }

  public function getLatitude(): ?float
  {
      return $this->latitude;
  }

  public function setLatitude(float $latitude): static
  {
      $this->latitude = $latitude;

      return $this;
  }

  public function getLongitude(): ?float
  {
      return $this->longitude;
  }

  public function setLongitude(float $longitude): static
  {
      $this->longitude = $longitude;

      return $this;
  }

  public function getDateSignalement(): ?\DateTime
  {
      return $this->dateSignalement;
  }

  public function setDateSignalement(\DateTime $dateSignalement): static
  {
      $this->dateSignalement = $dateSignalement;

      return $this;
  }

  public function getStatut(): ?StatutSignalement
  {
      return $this->statut;
  }

  public function setStatut(StatutSignalement $statut): static
  {
      $this->statut = $statut;

      return $this;
  }

  public function getPriorite(): ?int
  {
      return $this->priorite;
  }

  public function setPriorite(int $priorite): static
  {
      $this->priorite = $priorite;

      return $this;
  }

  public function getEtatValidation(): ?string
  {
      return $this->etatValidation;
  }

  public function setEtatValidation(string $etatValidation): static
  {
      $this->etatValidation = $etatValidation;

      return $this;
  }

  /**
   * Récupère l'état de validation sous forme d'enum
   */
  public function getEtatValidationEnum(): EtatValidation
  {
    return EtatValidation::from($this->etatValidation);
  }

  public function setEtatValidationEnum(EtatValidation $etat): static
  {
    $this->etatValidation = $etat->value;
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

  public function getCategorie(): ?Categorie
  {
      return $this->categorie;
  }

  public function setCategorie(?Categorie $categorie): static
  {
      $this->categorie = $categorie;

      return $this;
  }

  public function getVille(): ?Ville
  {
      return $this->ville;
  }

  public function setVille(?Ville $ville): static
  {
      $this->ville = $ville;

      return $this;
  }

  /**
   * @return Collection<int, Commentaire>
   */
  public function getCommentaires(): Collection
  {
      return $this->commentaires;
  }

  public function addCommentaire(Commentaire $commentaire): static
  {
      if (!$this->commentaires->contains($commentaire)) {
          $this->commentaires->add($commentaire);
          $commentaire->setSignalement($this);
      }

      return $this;
  }

  public function removeCommentaire(Commentaire $commentaire): static
  {
      if ($this->commentaires->removeElement($commentaire)) {
          // set the owning side to null (unless already changed)
          if ($commentaire->getSignalement() === $this) {
              $commentaire->setSignalement(null);
          }
      }

      return $this;
  }

  /**
   * @return Collection<int, JournalValidation>
   */
  public function getJournalValidations(): Collection
  {
      return $this->journalValidations;
  }

  public function addJournalValidation(JournalValidation $journalValidation): static
  {
      if (!$this->journalValidations->contains($journalValidation)) {
          $this->journalValidations->add($journalValidation);
          $journalValidation->setSignalement($this);
      }

      return $this;
  }

  public function removeJournalValidation(JournalValidation $journalValidation): static
  {
      if ($this->journalValidations->removeElement($journalValidation)) {
          // set the owning side to null (unless already changed)
          if ($journalValidation->getSignalement() === $this) {
              $journalValidation->setSignalement(null);
          }
      }

      return $this;
  }

  /**
   * @return Collection<int, Notification>
   */
  public function getNotifications(): Collection
  {
      return $this->notifications;
  }

  public function addNotification(Notification $notification): static
  {
      if (!$this->notifications->contains($notification)) {
          $this->notifications->add($notification);
          $notification->setSignalement($this);
      }

      return $this;
  }

  public function removeNotification(Notification $notification): static
  {
      if ($this->notifications->removeElement($notification)) {
          // set the owning side to null (unless already changed)
          if ($notification->getSignalement() === $this) {
              $notification->setSignalement(null);
          }
      }

      return $this;
  }

  public function getReparation(): ?Reparation
  {
      return $this->reparation;
  }

  public function setReparation(?Reparation $reparation): static
  {
      // unset the owning side of the relation if necessary
      if ($reparation === null && $this->reparation !== null) {
          $this->reparation->setSignalement(null);
      }

      // set the owning side of the relation if necessary
      if ($reparation !== null && $reparation->getSignalement() !== $this) {
          $reparation->setSignalement($this);
      }

      $this->reparation = $reparation;

      return $this;
  }

  public function getCluster(): ?Cluster
  {
      return $this->cluster;
  }

  public function setCluster(?Cluster $cluster): static
  {
      $this->cluster = $cluster;

      return $this;
  }

  // Ajoutez les getters et setters correspondants
  public function getDemandeSuppressionStatut(): ?string
  {
      return $this->demandeSuppressionStatut;
  }

  public function setDemandeSuppressionStatut(?string $statut): static
  {
      $this->demandeSuppressionStatut = $statut;
      if ($statut) {
          $this->dateDemandeSuppressionStatut = new \DateTime();
      }
      return $this;
  }

  public function getDateDemandeSuppressionStatut(): ?\DateTimeInterface
  {
      return $this->dateDemandeSuppressionStatut;
  }
}