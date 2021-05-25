<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Baraja\Doctrine\Identifier\Identifier;
use Baraja\Localization\TranslateObject;
use Baraja\Localization\Translation;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="cm__product_category")
 * @method Translation getName(?string $locale = null)
 * @method void setName(string $content, ?string $locale = null)
 * @method Translation|null getDescription(?string $locale = null)
 * @method void setDescription(?string $content = null, ?string $locale = null)
 */
class ProductCategory
{
	use Identifier;
	use TranslateObject;

	/** @ORM\ManyToOne(targetEntity="ProductCategory", inversedBy="child") */
	private ?self $parent;

	/**
	 * @var self[]|Collection
	 * @ORM\OneToMany(targetEntity="ProductCategory", mappedBy="parent")
	 */
	private $child;

	/** @ORM\Column(type="translate") */
	private Translation $name;

	/** @ORM\Column(type="string", length=64, unique=true) */
	private string $code;

	/** @ORM\Column(type="string", length=64, unique=true) */
	private string $slug;

	/** @ORM\Column(type="translate", nullable=true) */
	private ?Translation $description = null;

	/** @ORM\Column(type="integer", nullable=true) */
	private ?int $heurekaCategoryId = null;

	/**
	 * @var Product[]|Collection
	 * @ORM\OneToMany(targetEntity="Product", mappedBy="mainCategory")
	 */
	private $mainProduct;

	/**
	 * @var Product[]|Collection
	 * @ORM\ManyToMany(targetEntity="ProductCategory", mappedBy="products")
	 */
	private $products;


	public function getRootId(): int
	{
		if (($parent = $this->getParent()) !== null) {
			return $parent->getRootId();
		}

		return $this->getId();
	}


	/**
	 * @return int[]
	 */
	public function getAllChildIds(): array
	{
		$return = [$this->getId()];
		foreach ($this->getChild() as $child) {
			$return[] = $child->getId();
		}

		return array_unique($return);
	}


	public function getParent(): ?ProductCategory
	{
		return $this->parent;
	}


	/**
	 * @return ProductCategory[]|Collection
	 */
	public function getChild()
	{
		return $this->child;
	}


	public function getCode(): string
	{
		return $this->code;
	}


	public function getSlug(): string
	{
		return $this->slug;
	}


	public function getHeurekaCategoryId(): ?int
	{
		return $this->heurekaCategoryId;
	}


	public function setHeurekaCategoryId(?int $heurekaCategoryId): void
	{
		$this->heurekaCategoryId = $heurekaCategoryId;
	}


	/**
	 * @return Product[]|Collection
	 */
	public function getMainProduct()
	{
		return $this->mainProduct;
	}


	/**
	 * @return Product[]|Collection
	 */
	public function getProducts()
	{
		return $this->products;
	}
}
