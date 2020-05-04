<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={
 *          "groups"={"material:read"},
 *          "swagger_definition_name"="Read"
 *     },
 *     denormalizationContext={
 *          "groups"={"material:write"},
 *          "swagger_definition_name"="Write"
 *     },
 *     collectionOperations={
 *          "get"={
 *              "security"="is_granted('ROLE_COVER_CRUD')"
 *          },
 *          "post"={
 *              "security"="is_granted('ROLE_COVER_CRUD')"
 *          }
 *      },
 *     itemOperations={
 *          "get"={
 *              "security"="is_granted('ROLE_COVER_CRUD')"
 *          },
 *          "delete"={
 *              "security"="is_granted('ROLE_COVER_CRUD')"
 *          }
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\CoverRepository")
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
     * @Groups({"material:read", "material:write"})
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
     * @Groups({"material:read", "material:write"})
     */
    private $isType;

    /**
     * @ORM\Column(type="string", length=16)
     * @Groups({"material:read"})
     */
    private $agencyId;

    /**
     * @var Cover|null
     *
     * @ORM\ManyToOne(targetEntity=Cover::class)
     * @ORM\JoinColumn(nullable=true)
     * @ApiProperty(iri="http://schema.org/image")
     * @Groups({"material:read", "material:write"})
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
