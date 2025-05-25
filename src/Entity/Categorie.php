<?php

namespace App\Entity;

use App\Repository\CategorieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategorieRepository::class)]
class Categorie
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  private ?int $id = null;

  #[ORM\Column(length: 255)]
  private ?string $nom = null;

  #[ORM\Column(type: 'text', nullable: true)]
  private ?string $description = null;

  #[ORM\OneToMany(mappedBy: 'categorie', targetEntity: Signalement::class)]
  private Collection $signalements;

  public function __construct()
  {
    $this->signalements = new ArrayCollection();
  }

  // Getters et setters...
}