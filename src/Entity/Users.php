<?php

namespace App\Entity;

use App\Repository\UsersRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UsersRepository::class)]
class Users
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 40, unique: true)]
    private ?string $username = null;

    #[ORM\Column(length: 100)]
    private ?string $password = null;

    #[ORM\Column]
    private ?bool $admin = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    private array $lastArtists = [];

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    private array $lastAlbums = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function isAdmin(): ?bool
    {
        return $this->admin;
    }

    public function setAdmin(bool $admin): self
    {
        $this->admin = $admin;

        return $this;
    }

    public function getLastArtists(): array
    {
        return $this->lastArtists;
    }

    public function setLastArtists(?array $lastArtists): self
    {
        $this->lastArtists = $lastArtists;

        return $this;
    }

    public function getLastAlbums(): array
    {
        return $this->lastAlbums;
    }

    public function setLastAlbums(?array $lastAlbums): self
    {
        $this->lastAlbums = $lastAlbums;

        return $this;
    }
}
