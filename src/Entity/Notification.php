<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  private ?int $id = null;

  #[ORM\Column(type: 'text')]
  private ?string $message = null;

  #[ORM\Column(type: Types::DATETIME_MUTABLE)]
  private ?\DateTimeInterface $dateEnvoi = null;

  #[ORM\Column(length: 50)]
  private ?string $type = null;

  #[ORM\Column(length: 50)]
  private ?string $statut = 'non_lue';

  #[ORM\ManyToOne(inversedBy: 'notifications')]
  #[ORM\JoinColumn(nullable: false)]
  private ?Utilisateur $destinataire = null;

  #[ORM\ManyToOne(inversedBy: 'notifications')]
  private ?Signalement $signalement = null;

  public function __construct()
  {
    $this->dateEnvoi = new \DateTime();
  }

  // Getters et setters...

  public function getId(): ?int
  {
      return $this->id;
  }

  public function getMessage(): ?string
  {
      return $this->message;
  }

  public function setMessage(string $message): static
  {
      $this->message = $message;

      return $this;
  }

  public function getDateEnvoi(): ?\DateTime
  {
      return $this->dateEnvoi;
  }

  public function setDateEnvoi(\DateTime $dateEnvoi): static
  {
      $this->dateEnvoi = $dateEnvoi;

      return $this;
  }

  public function getType(): ?string
  {
      return $this->type;
  }

  public function setType(string $type): static
  {
      $this->type = $type;

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

  public function getDestinataire(): ?Utilisateur
  {
      return $this->destinataire;
  }

  public function setDestinataire(?Utilisateur $destinataire): static
  {
      $this->destinataire = $destinataire;

      return $this;
  }

  public function getSignalement(): ?Signalement
  {
      return $this->signalement;
  }

  public function setSignalement(?Signalement $signalement): static
  {
      $this->signalement = $signalement;

      return $this;
  }
}