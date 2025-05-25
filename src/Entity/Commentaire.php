<?php

namespace App\Entity;

use App\Repository\CommentaireRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentaireRepository::class)]
class Commentaire
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  private ?int $id = null;

  #[ORM\Column(type: 'text')]
  private ?string $contenu = null;

  #[ORM\Column(type: Types::DATETIME_MUTABLE)]
  private ?\DateTimeInterface $dateCommentaire = null;

  #[ORM\Column(length: 50)]
  private ?string $etatValidation = 'en_attente';

  #[ORM\ManyToOne(inversedBy: 'commentaires')]
  #[ORM\JoinColumn(nullable: false)]
  private ?Utilisateur $utilisateur = null;

  #[ORM\ManyToOne(inversedBy: 'commentaires')]
  #[ORM\JoinColumn(nullable: false)]
  private ?Signalement $signalement = null;

  public function __construct()
  {
    $this->dateCommentaire = new \DateTime();
  }

  // Getters et setters...
}