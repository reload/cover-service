<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\CreateCoverAction;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity
 * @ApiResource(
 *     iri="http://schema.org/MediaObject",
 *     normalizationContext={
 *         "groups"={"cover_read"}
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
 *                                     "file"={
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
 * @Vich\Uploadable
 */
class Cover
{
    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     * @ORM\Id
     * @Groups({"cover_read"})
     */
    protected $id;

    /**
     * @var string|null
     *
     * @ApiProperty(iri="http://schema.org/contentUrl")
     * @Groups({"cover_read"})
     */
    private $imageUrl;

    /**
     * @var File|null
     *
     * @Assert\File(
     *     maxSize = "6144k",
     *     mimeTypes = {"image/jpeg", "image/png"},
     *     mimeTypesMessage = "Please upload a valid jpeg or png"
     * )
     * @Assert\NotNull(groups={"cover_create"})
     * @Vich\UploadableField(mapping="cover", fileNameProperty="filePath", size="size")
     */
    private $file;

    /**
     * @var string|null
     *
     * @ORM\Column(nullable=true)
     */
    private $filePath;

    /**
     * @ORM\Column(type="integer")
     *
     * @var int
     * @Groups({"cover_read"})
     */
    private $size;

    /**
     * @ORM\Column(type="datetime")
     *
     * @var \DateTime
     * @Groups({"cover_read"})
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="string", length=16)
     * @Groups({"cover_read"})
     */
    private $agencyId;

    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default":false})
     * @Groups({"cover_read"})
     */
    private $isUploaded = false;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * @param string $imageUrl
     *
     * @return $this
     */
    public function setImageUrl(string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    /**
     * @param File|null $file
     *
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

    /**
     * @return File|null
     */
    public function getFile(): ?File
    {
        return $this->file;
    }

    /**
     * @param string|null $filePath
     *
     * @return $this
     */
    public function setFilePath(?string $filePath): self
    {
        $this->filePath = $filePath;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    /**
     * @param int|null $size
     *
     * @return $this
     */
    public function setSize(?int $size): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * @return string|null
     */
    public function getAgencyId(): ?string
    {
        return $this->agencyId;
    }

    /**
     * @param string $agencyId
     *
     * @return $this
     */
    public function setAgencyId(string $agencyId): self
    {
        $this->agencyId = $agencyId;

        return $this;
    }

    /**
     * @return bool
     */
    public function isUploaded(): bool
    {
        return $this->isUploaded;
    }

    /**
     * @param bool $isUploaded
     *
     * @return $this
     */
    public function setUploaded(bool $isUploaded): self
    {
        $this->isUploaded = $isUploaded;

        return $this;
    }
}
