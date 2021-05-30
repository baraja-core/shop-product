<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Baraja\Doctrine\Identifier\Identifier;
use Baraja\Localization\TranslateObject;
use Baraja\Localization\Translation;
use Baraja\Shop\Product\BeautifulPrice;
use Baraja\Shop\Product\Validators;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nette\Utils\Floats;
use Nette\Utils\Strings;

/**
 * @ORM\Entity()
 * @ORM\Table(name="shop__product")
 * @method Translation getName(?string $locale = null)
 * @method void setName(string $content, ?string $locale = null)
 * @method Translation|null getShortDescription(?string $locale = null)
 * @method void setShortDescription(?string $content = null, ?string $locale = null)
 */
class Product
{
	use Identifier;
	use TranslateObject;

	/** @ORM\Column(type="translate") */
	private Translation $name;

	/** @ORM\Column(type="string", length=64, unique=true) */
	private string $code;

	/** @ORM\Column(type="string", length=64, unique=true, nullable=true) */
	private ?string $ean;

	/** @ORM\Column(type="string", length=80, unique=true) */
	private string $slug;

	/** @ORM\Column(type="integer", nullable=true, unique=true) */
	private ?int $oldId = null;

	/** @ORM\ManyToOne(targetEntity="ProductImage") */
	private ?ProductImage $mainImage;

	/**
	 * @var ProductImage[]|Collection
	 * @ORM\OneToMany(targetEntity="ProductImage", mappedBy="product")
	 */
	private $images;

	/** @ORM\Column(type="translate", nullable=true) */
	private ?Translation $shortDescription;

	/**
	 * @deprecated since 2021-03-10
	 * @ORM\Column(type="text", nullable=true)
	 */
	private ?string $description = null;

	/** @ORM\Column(type="float") */
	private float $price;

	/** @ORM\Column(type="float", nullable=true) */
	private ?float $standardPricePercentage = null;

	/** @ORM\Column(type="integer") */
	private int $position = 0;

	/** @ORM\Column(type="boolean") */
	private bool $active = false;

	/** @ORM\Column(type="boolean") */
	private bool $showInFeed = true;

	/** @ORM\Column(type="boolean") */
	private bool $soldOut = true;

	/** @ORM\Column(type="smallint", nullable=true) */
	private ?int $vat = null;

	/** @ORM\ManyToOne(targetEntity="ProductCategory", inversedBy="mainProducts") */
	private ?ProductCategory $mainCategory;

	/**
	 * @var ProductCategory[]|Collection
	 * @ORM\ManyToMany(targetEntity="ProductCategory", inversedBy="products")
	 */
	private $categories;

	/**
	 * @var ProductLabel[]|Collection
	 * @ORM\ManyToMany(targetEntity="ProductLabel", inversedBy="products")
	 */
	private $labels;

	/**
	 * @var ProductSmartDescription[]|Collection
	 * @ORM\OneToMany(targetEntity="ProductSmartDescription", mappedBy="product")
	 */
	private $smartDescriptions;

	/**
	 * @var ProductParameter[]|Collection
	 * @ORM\OneToMany(targetEntity="ProductParameter", mappedBy="product")
	 */
	private $parameters;

	/**
	 * @var ProductVariant[]|Collection
	 * @ORM\OneToMany(targetEntity="ProductVariant", mappedBy="product")
	 */
	private $variants;

	/**
	 * @var RelatedProduct[]|Collection
	 * @ORM\OneToMany(targetEntity="RelatedProduct", mappedBy="product")
	 */
	private $productRelatedBasic;

	/**
	 * @var RelatedProduct[]|Collection
	 * @ORM\OneToMany(targetEntity="RelatedProduct", mappedBy="relatedProduct")
	 */
	private $productRelatedRelated;


	public function __construct(string $name, string $code, float $price)
	{
		$this->setName($name);
		$this->setCode($code);
		$this->setSlug($name);
		$this->setPrice($price);
		$this->images = new ArrayCollection;
		$this->categories = new ArrayCollection;
		$this->labels = new ArrayCollection;
		$this->smartDescriptions = new ArrayCollection;
		$this->parameters = new ArrayCollection;
		$this->variants = new ArrayCollection;
		$this->productRelatedBasic = new ArrayCollection;
		$this->productRelatedRelated = new ArrayCollection;
	}


	public function isVariantProduct(): bool
	{
		return $this->variants->count() > 0;
	}


	/**
	 * @return array<int, array<string, string|int>>
	 */
	public function getCategoriesTree(): array
	{
		if ($this->mainCategory === null) {
			return [];
		}

		$return = [];
		$category = $this->mainCategory;
		do {
			$return[] = [
				'id' => (int) $category->getId(),
				'name' => (string) $category->getName(),
				'slug' => $category->getSlug(),
			];
			$category = $category->getParent();
		} while ($category !== null);

		return array_reverse($return);
	}


	public function getCode(): string
	{
		return $this->code;
	}


	public function setCode(string $code): void
	{
		$this->code = Strings::webalize($code);
	}


	public function getEanForce(): string
	{
		return $this->getEan() ?? $this->getCode();
	}


	public function getEan(): ?string
	{
		return $this->ean;
	}


	public function setEan(?string $ean): void
	{
		if ($ean !== null) {
			$ean = Strings::webalize($ean);
			if (Validators::validateEAN13($ean) === false) {
				throw new \InvalidArgumentException(
					'EAN "' . $ean . '" is not valid. Please read EAN-13 specification.'
					. "\n" . 'To solve this issue: Please read https://en.wikipedia.org/wiki/International_Article_Number.',
				);
			}
		}
		$this->ean = $ean;
	}


	public function getSlug(): string
	{
		return $this->slug;
	}


	public function setSlug(string $slug): void
	{
		$this->slug = Strings::webalize($slug, '/');
	}


	public function getOldId(): ?int
	{
		return $this->oldId;
	}


	public function setOldId(?int $oldId): void
	{
		$this->oldId = $oldId;
	}


	public function getMainImage(): ?ProductImage
	{
		return $this->mainImage;
	}


	public function setMainImage(?ProductImage $mainImage): void
	{
		$this->mainImage = $mainImage;
	}


	public function getPrice(): float
	{
		return $this->price;
	}


	public function setPrice(float $price): void
	{
		$this->price = $price;
	}


	public function isSale(): bool
	{
		return ($this->standardPricePercentage ?: 0) > 0;
	}


	public function getStandardPrice(): float
	{
		return BeautifulPrice::from(
			$this->isSale()
				? ($this->standardPricePercentage / 100) * $this->getPrice()
				: $this->getPrice()
		)->smartRound();
	}


	public function getSalePrice(): float
	{
		$return = $this->isSale()
			? $this->getPrice() - $this->getStandardPrice()
			: $this->getPrice();

		return $return < 0 ? 0 : $return;
	}


	public function getStandardPricePercentage(): ?float
	{
		return $this->standardPricePercentage;
	}


	public function setStandardPricePercentage(?float $standardPricePercentage): void
	{
		if ($standardPricePercentage !== null && Floats::isZero($standardPricePercentage)) {
			$standardPricePercentage = null;
		}
		$this->standardPricePercentage = $standardPricePercentage;
	}


	public function getPosition(): int
	{
		return $this->position;
	}


	public function setPosition(int $position): void
	{
		if ($position < 0) {
			$position = 0;
		}
		if ($position > 1000) {
			$position = 1000;
		}
		$this->position = $position;
	}


	public function isActive(): bool
	{
		return $this->active;
	}


	public function setActive(bool $active): void
	{
		$this->active = $active;
	}


	public function isShowInFeed(): bool
	{
		return $this->showInFeed;
	}


	public function setShowInFeed(bool $showInFeed): void
	{
		$this->showInFeed = $showInFeed;
	}


	public function isSoldOut(): bool
	{
		return $this->soldOut;
	}


	public function setSoldOut(bool $soldOut): void
	{
		$this->soldOut = $soldOut;
	}


	public function getMainCategory(): ?ProductCategory
	{
		return $this->mainCategory;
	}


	public function setMainCategory(?ProductCategory $mainCategory): void
	{
		$this->mainCategory = $mainCategory;
	}


	public function getVat(int $default = 21): int
	{
		return $this->vat ?? $default;
	}


	public function setVat(?int $vat): void
	{
		if ($vat !== null && $vat < 0) {
			$vat = 0;
		}
		$this->vat = $vat;
	}


	/**
	 * @return ProductCategory[]|Collection
	 */
	public function getCategories()
	{
		return $this->categories;
	}


	public function addCategory(ProductCategory $category): void
	{
		$this->categories[] = $category;
	}


	public function removeCategory(ProductCategory $category): void
	{
		$return = new ArrayCollection;
		foreach ($this->categories as $item) {
			if ($item->getId() !== $category->getId()) {
				$return->add($item);
			}
		}
		$this->categories = $return;
	}


	/**
	 * @return ProductLabel[]|Collection
	 */
	public function getLabels()
	{
		return $this->labels;
	}


	/**
	 * @return ProductSmartDescription[]|Collection
	 */
	public function getSmartDescriptions()
	{
		return $this->smartDescriptions;
	}


	/**
	 * @return ProductImage[]|Collection
	 */
	public function getImages()
	{
		return $this->images;
	}


	/**
	 * @return ProductParameter[]|Collection
	 */
	public function getParameters()
	{
		return $this->parameters;
	}


	/**
	 * @return ProductVariant[]|Collection
	 */
	public function getVariants()
	{
		return $this->variants;
	}
}
