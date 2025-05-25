<?php

namespace App\Entity;

use App\Repository\ClusterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClusterRepository::class)]
class Cluster
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  private ?int $id = null;

  #[ORM\Column]
  private ?float $latitude = null;

  #[ORM\Column]
  private ?float $longitude = null;

  #[ORM\Column]
  private ?float $rayon = null;

  #[ORM\Column]
  private ?int $nombreSignalements = 0;

  #[ORM\ManyToOne(inversedBy: 'clusters')]
  #[ORM\JoinColumn(nullable: false)]
  private ?Ville $ville = null;

  #[ORM\OneToMany(mappedBy: 'cluster', targetEntity: Signalement::class)]
  private Collection $signalements;

  public function __construct()
  {
    $this->signalements = new ArrayCollection();
  }

  // Getters et setters...
}