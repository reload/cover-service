<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\CreateCoverAction;
use App\Repository\CoverRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity(repositoryClass=CoverRepository::class)
 *
 * @ORM\Table(
 *     name="cover",
 *     indexes={
 *
 *          @ORM\Index(name="agency_idx", columns={"agency_id"})
 *     }
 * )
 *
 * @ApiResource(
 *     attributes={
 *          "order"={
 *              "id": "DESC"
 *          }
 *     },
 *     iri="https://schema.org/MediaObject",
 *     normalizationContext={
 *         "groups"={"read"},
 *         "swagger_definition_name"="Read"
 *     },
 *     collectionOperations={
 *         "post"={
 *             "security"="is_granted('ROLE_COVER_CRUD')",
 *             "controller"=CreateCoverAction::class,
 *             "deserialize"=false,
 *             "validation_groups"={"Default", "cover_create"},
 *             "openapi_context"={
 *                 "requestBody"={
 *                     "content"={
 *                         "multipart/form-data"={
 *                             "schema"={
 *                                 "type"="object",
 *                                 "properties"={
 *                                     "cover"={
 *                                         "type"="string",
 *                                         "format"="binary"
 *                                     }
 *                                 }
 *                             }
 *                         }
 *                     }
 *                 }
 *             }
 *         },
 *         "get"={"security"="is_granted('ROLE_COVER_CRUD')"}
 *     },
 *     itemOperations={
 *         "get"={"security"="is_granted('ROLE_COVER_CRUD')"}
 *     }
 * )
 *
 * @Vich\Uploadable
 */
class Cover
{
    /**
     * @ORM\Column(type="integer")
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Id
     *
     * @Groups({"read"})
     */
    protected int $id;

    /**
     * @ApiProperty(
     *     iri="https://schema.org/contentUrl",
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "format"="url",
     *             "example"="https://upload.cover.dandigbib.org/cover/5ed65baa2b264_870970-basis%3A52890365.jpg"
     *         }
     *     }
     * )
     *
     * @Groups({"read"})
     */
    private ?string $imageUrl;

    /**
     * @Assert\File(
     *     maxSize = "6144k",
     *     mimeTypes = {"image/jpeg", "image/png"},
     *     mimeTypesMessage = "Please upload a valid jpeg or png"
     * )
     *
     * @Assert\NotNull(groups={"cover_create"})
     *
     * @Vich\UploadableField(mapping="cover", fileNameProperty="filePath", size="size")
     */
    private ?File $file;

    /**
     * @ORM\Column(nullable=true)
     */
    private ?string $filePath;

    /**
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="integer",
     *             "example"=769822
     *         }
     *     }
     * )
     *
     * @ORM\Column(type="integer", nullable="true")
     *
     * @Groups({"read"})
     */
    private ?int $size;

    /**
     * @ORM\Column(type="datetime_immutable")
     *
     * @Groups({"read"})
     */
    private \DateTimeImmutable $updatedAt;

    /**
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="123456"
     *         }
     *     }
     * )
     *
     * @ORM\Column(type="string", length=16)
     *
     * @Groups({"read"})
     */
    private ?string $agencyId;

    /**
     * @ORM\Column(type="boolean", options={"default":false})
     */
    private bool $isUploaded = false;

    /**
     * @var ?string
     *
     * @ORM\Column(type="string", nullable="true", options={"default":null})
     */
    private ?string $remoteUrl;

    /**
     * @ORM\OneToOne(targetEntity=Material::class, mappedBy="cover", cascade={"persist", "remove"})
     */
    private ?Material $material;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * @return $this
     */
    public function setImageUrl(string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    /**
     * @return $this
     */
    public function setFile(?File $file = null): self
    {
        $this->file = $file;

        if (null !== $file) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): void
    {
        if ($updatedAt instanceof \DateTime) {
            $updatedAt = \DateTimeImmutable::createFromMutable($updatedAt);
        }

        $this->updatedAt = $updatedAt;
    }

    public function getFile(): ?File
    {
        return $this->file ?? null;
    }

    /**
     * @return $this
     */
    public function setFilePath(?string $filePath): self
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    /**
     * @return $this
     */
    public function setSize(?int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getAgencyId(): ?string
    {
        return $this->agencyId;
    }

    /**
     * @return $this
     */
    public function setAgencyId(string $agencyId): self
    {
        $this->agencyId = $agencyId;

        return $this;
    }

    public function isUploaded(): bool
    {
        return $this->isUploaded;
    }

    /**
     * @return $this
     */
    public function setUploaded(bool $isUploaded): self
    {
        $this->isUploaded = $isUploaded;

        return $this;
    }

    public function getRemoteUrl(): ?string
    {
        return $this->remoteUrl;
    }

    public function setRemoteUrl(string $url): self
    {
        $this->remoteUrl = $url;

        return $this;
    }

    public function getMaterial(): Material
    {
        if (null === $this->material) {
            throw new UninitializedPropertyException();
        }

        return $this->material;
    }

    public function setMaterial(?Material $material): self
    {
        // unset the owning side of the relation if necessary
        if (null === $material && null !== $this->material) {
            $this->material->setCover(null);
        }

        // set the owning side of the relation if necessary
        if (null !== $material && $material->getCover() !== $this) {
            $material->setCover($this);
        }

        $this->material = $material;

        return $this;
    }

    public function __toString()
    {
        $str = [];
        $str[] = str_repeat('-', 16).' Cover '.str_repeat('-', 16);
        $str[] = "Id:\t\t$this->id";
        $str[] = "Agency ID:\t$this->agencyId";
        $str[] = "Is uploaded:\t$this->isUploaded";
        $str[] = "File:\t$this->file";
        $str[] = "Size:\t$this->size";
        $str[] = str_repeat('-', 39);

        return implode("\n", $str)."\n";
    }
}
