<?php

namespace App\Entity;

use App\Repository\VilleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: VilleRepository::class)]
class Ville
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  private ?int $id = null;

  #[ORM\Column(length: 255)]
  private ?string $nom = null;

  #[ORM\Column(length: 255, unique: true)]
  #[Gedmo\Slug(fields: ['nom'])]
  private ?string $slug = null;

  #[ORM\Column]
  private ?float $latitudeCentre = null;

  #[ORM\Column]
  private ?float $longitudeCentre = null;

  #[ORM\OneToMany(mappedBy: 'villeResidence', targetEntity: Utilisateur::class)]
  private Collection $utilisateurs;

  #[ORM\OneToMany(mappedBy: 'ville', targetEntity: Signalement::class)]
  private Collection $signalements;

  #[ORM\OneToMany(mappedBy: 'ville', targetEntity: Cluster::class)]
  private Collection $clusters;

  public function __construct()
  {
    $this->utilisateurs = new ArrayCollection();
    $this->signalements = new ArrayCollection();
    $this->clusters = new ArrayCollection();
  }

  // Getters et setters...
}