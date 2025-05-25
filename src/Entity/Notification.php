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
}