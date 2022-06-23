<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={
 *          "order"={
 *              "id": "DESC"
 *          }
 *     },
 *     normalizationContext={
 *          "groups"={"read"},
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
 * @ORM\Entity
 * @ORM\Table(
 *     name="material",
 *     indexes={
 *          @ORM\Index(name="agency_idx", columns={"agency_id"}),
 *          @ORM\Index(name="is_idx", columns={"agency_id", "is_identifier", "is_type"})
 *     }
 * )
 */
class Material
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"read"})
     */
    private int $id;

    /**
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="870970-basis:29862885"
     *         }
     *     }
     * )
     * @ORM\Column(type="string", length=50)
     * @Groups({"read", "material:write"})
     */
    private ?string $isIdentifier;

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
     *
     * @Groups({"read", "material:write"})
     */
    private ?string $isType;

    /**
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="123456"
     *         }
     *     }
     * )
     * @ORM\Column(type="string", length=16)
     *
     * @Groups({"read"})
     */
    private ?string $agencyId;

    /**
     * @var Cover|null
     *
     * @ApiProperty(
     *     iri="http://schema.org/image",
     *     attributes={
     *         "openapi_context"={
     *             "type"="object",
     *             "example"={
     *                  "id": 1,
     *                  "imageUrl": "https://upload.cover.dandigbib.org/cover/5ed65baa2b264_870970-basis%3A52890365.jpg",
     *                  "size": 1478312,
     *                  "agencyId": "123456"
     *              }
     *         }
     *     }
     * )
     *
     * @ORM\OneToOne(targetEntity=Cover::class, inversedBy="material", cascade={"persist", "remove"})
     *
     * @Groups({"read", "material:write"})
     */
    public ?Cover $cover;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIsIdentifier(): string
    {
        if (null === $this->isIdentifier) {
            throw new UninitializedPropertyException();
        }

        return $this->isIdentifier;
    }

    public function setIsIdentifier(string $isIdentifier): self
    {
        $this->isIdentifier = $isIdentifier;

        return $this;
    }

    public function getIsType(): string
    {
        if (null === $this->isType) {
            throw new UninitializedPropertyException();
        }

        return $this->isType;
    }

    public function setIsType(string $isType): self
    {
        $this->isType = $isType;

        return $this;
    }

    public function getAgencyId(): string
    {
        if (null === $this->agencyId) {
            throw new UninitializedPropertyException();
        }

        return $this->agencyId;
    }

    public function setAgencyId(string $agencyId): self
    {
        $this->agencyId = $agencyId;

        return $this;
    }

    public function getCover(): Cover
    {
        if (null === $this->cover) {
            throw new UninitializedPropertyException();
        }

        return $this->cover;
    }

    public function setCover(?Cover $cover): self
    {
        $this->cover = $cover;

        return $this;
    }

    public function __toString()
    {
        $str = [];
        $str[] = str_repeat('-', 14).' Material '.str_repeat('-', 14);
        $str[] = "Id:\t\t$this->id";
        $str[] = "Type:\t\t$this->isType";
        $str[] = "Identifier:\t$this->isIdentifier";
        $str[] = "Agency ID:\t$this->agencyId";
        $str[] = str_repeat('-', 38);

        return implode("\n", $str)."\n";
    }
}
