<?php

/**
 * @file
 * Wrapper for information returned from vendor image host.
 */

namespace App\Utils\CoverVendor;

use App\Entity\Vendor;

class VendorImageItem implements \Stringable
{
    private bool $found = false;
    private bool $updated = false;
    private bool $genericCover = false;
    private Vendor $vendor;
    private string $originalFile;
    private \DateTime $originalLastModified;
    private int $originalContentLength = 0;
    private string $eTag;

    /**
     * @param Vendor $vendor
     * @param string $originalFile
     */
    public function __construct(string $originalFile, Vendor $vendor)
    {
        $this->originalFile = $originalFile;
        $this->vendor = $vendor;
    }

    public function __toString(): string
    {
        $output = [];

        $output[] = str_repeat('-', 42);
        $output[] = 'Vendor: '.$this->getVendor()->getName();
        $output[] = 'Original file: '.$this->getOriginalFile();
        $output[] = 'Content length: '.$this->getOriginalContentLength();
        $output[] = 'Last modified: '.$this->getOriginalLastModified()->format('r');
        $output[] = 'Is found: '.($this->isFound() ? 'yes' : 'no');

        $output[] = str_repeat('-', 42);

        return implode("\n", $output);
    }

    public function isFound(): bool
    {
        return $this->found;
    }

    /**
     * @return static
     */
    public function setFound(bool $found): self
    {
        $this->found = $found;

        return $this;
    }

    public function isUpdated(): bool
    {
        return $this->updated;
    }

    /**
     * @return static
     */
    public function setUpdated(bool $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    public function isGenericCover(): bool
    {
        return $this->genericCover;
    }

    public function setGenericCover(bool $genericCover): self
    {
        $this->genericCover = $genericCover;

        return $this;
    }

    public function getVendor(): Vendor
    {
        return $this->vendor;
    }

    /**
     * @param Vendor $vendor
     *
     * @return static
     */
    public function setVendor(Vendor $vendor): self
    {
        $this->vendor = $vendor;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOriginalFile(): string
    {
        return $this->originalFile;
    }

    /**
     * @return static
     */
    public function setOriginalFile(string $originalFile): self
    {
        $this->originalFile = $originalFile;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOriginalLastModified(): ?\DateTime
    {
        return $this->originalLastModified;
    }

    /**
     * @param mixed $originalLastModified
     *
     * @return static
     */
    public function setOriginalLastModified(?\DateTime $originalLastModified): self
    {
        $this->originalLastModified = $originalLastModified;

        return $this;
    }

    /**
     * @return int
     */
    public function getOriginalContentLength(): int
    {
        return $this->originalContentLength;
    }

    /**
     * @param mixed $originalContentLength
     *
     * @return static
     */
    public function setOriginalContentLength(?int $originalContentLength): self
    {
        if (null !== $originalContentLength) {
            $this->originalContentLength = $originalContentLength;
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getETag(): ?string
    {
        return $this->eTag;
    }

    /**
     * @return static
     */
    public function setETag(string $eTag): self
    {
        $this->eTag = $eTag;

        return $this;
    }
}
