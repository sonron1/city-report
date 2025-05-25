<?php

namespace App\Entity;

use App\Repository\RouteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RouteRepository::class)]
class Route
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(nullable: true)]
    private ?float $longueur = null;

    #[ORM\Column]
    private ?int $voies = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getLongueur(): ?float
    {
        return $this->longueur;
    }

    public function setLongueur(?float $longueur): static
    {
        $this->longueur = $longueur;

        return $this;
    }

    public function getVoies(): ?int
    {
        return $this->voies;
    }

    public function setVoies(int $voies): static
    {
        $this->voies = $voies;

        return $this;
    }
}
