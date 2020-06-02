<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
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
    private $id;

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
     *
     * @Groups({"read", "material:write"})
     */
    private $isType;

    /**
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="870970"
     *         }
     *     }
     * )
     * @ORM\Column(type="string", length=16)
     *
     * @Groups({"read"})
     */
    private $agencyId;

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
     *                  "agencyId": "870970"
     *              }
     *         }
     *     }
     * )
     *
     * @ORM\ManyToOne(targetEntity=Cover::class, fetch="EAGER", cascade={"remove"})
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     *
     * @Groups({"read", "material:write"})
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
