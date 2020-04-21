<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource(
 *     collectionOperations={
 *          "get"={"security"="is_granted('ROLE_COVER_CRUD')"},
 *          "post"={"security"="is_granted('ROLE_COVER_CRUD')"}
 *      },
 *     itemOperations={
 *          "get"={"security"="is_granted('ROLE_COVER_CRUD')"},
 *          "delete"={"security"="is_granted('ROLE_COVER_CRUD')"}
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\MaterialRepository")
 */
class Material
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $isIdentifier;

    /**
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"faust", "isbn", "issn", "pid"},
     *             "example"="pid"
     *         }
     *     }
     * )
     * @ORM\Column(type="string", length=5)
     */
    private $isType;

    /**
     * @ORM\Column(type="string", length=16)
     */
    private $agencyId;

    /**
     * @var Cover|null
     *
     * @ORM\ManyToOne(targetEntity=Cover::class)
     * @ORM\JoinColumn(nullable=true)
     * @ApiProperty(iri="http://schema.org/image")
     */
    public $cover;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIsIdentifier(): ?string
    {
        return $this->isIdentifier;
    }

    public function setIsIdentifier(string $isIdentifier): self
    {
        $this->isIdentifier = $isIdentifier;

        return $this;
    }

    public function getIsType(): ?string
    {
        return $this->isType;
    }

    public function setIsType(string $isType): self
    {
        $this->isType = $isType;

        return $this;
    }

    public function getAgencyId(): ?string
    {
        return $this->agencyId;
    }

    public function setAgencyId(string $agencyId): self
    {
        $this->agencyId = $agencyId;

        return $this;
    }
}
