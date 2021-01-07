<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ProductRepository::class)
 */
class Product
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Assert\Regex("/^[a-z\-0-9]+$/i")
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank
     */
    private $price;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OrderMap", mappedBy="product")
     */
    private $orderMap;

    public function __construct()
    {
        $this->orderMap = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getOrderMap(): Collection
    {
        return $this->orderMap;
    }

    public function addOrderMap(OrderMap $orderMap): self
    {
        if (!$this->orderMap->contains($orderMap)) {
            $this->orderMap[] = $orderMap;
            $orderMap->setProduct($this);
        }

        return $this;
    }

    public function removeOrderMap(OrderMap $orderMap): self
    {
        if ($this->orderMap->removeElement($orderMap)) {
            // set the owning side to null (unless already changed)
            if ($orderMap->getProduct() === $this) {
                $orderMap->setProduct(null);
            }
        }

        return $this;
    }
}
