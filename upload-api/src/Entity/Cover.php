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
 *             "security"="is_granted('ROLE_API_PLATFORM')"
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
 *         "get"={"security"="is_granted('ROLE_API_PLATFORM')"}
 *     },
 *     itemOperations={
 *         "get"={"security"="is_granted('ROLE_API_PLATFORM')"}
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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

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

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFilePath(?string $filePath): self
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setSize(?int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }
}
