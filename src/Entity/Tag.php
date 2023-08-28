<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Repository\TagRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
#[ApiResource(
operations: [
    new Get(),
    new GetCollection(),
    new Post(security: "is_granted('ROLE_USER')"),
    new Put(security: "is_granted('ROLE_USER')"),
    new Patch(security: "is_granted('ROLE_USER')"),
    new Delete(security: "is_granted('ROLE_ADMIN')")
]
)]
#[ORM\Entity(repositoryClass: TagRepository::class)]
#[UniqueEntity("name")]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string', length: 25, nullable: true)]
    #[Groups(['read:Post'])]
    private $name;
    public function getId() : ?int
    {
        return $this->id;
    }
    public function getName() : ?string
    {
        return $this->name;
    }
    public function setName(?string $name) : self
    {
        $this->name = $name;
        return $this;
    }
}
