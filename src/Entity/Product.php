<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Baraja\EcommerceStandard\DTO\ProductInterface;
use Baraja\Localization\TranslateObject;
use Baraja\Localization\Translation;
use Baraja\Shop\Brand\Entity\Brand;
use Baraja\Shop\Price\Price;
use Baraja\Shop\Product\Repository\ProductRepository;
use Baraja\Shop\Product\Validators;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nette\Utils\Strings;

/**
 * @method Translation getName(?string $locale = null)
 * @method void setName(string $content, ?string $locale = null)
 * @method Translation|null getShortDescription(?string $locale = null)
 * @method void setShortDescription(?string $content = null, ?string $locale = null)
 * @method Translation|null getDescription(?string $locale = null)
 * @method void setDescription(?string $content = null, ?string $locale = null)
 * @method Translation|null getInternalNote(?string $locale = null)
 * @method void setInternalNote(?string $content = null, ?string $locale = null)
 */
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'shop__product')]
class Product implements ProductInterface
{
	use TranslateObject;

	#[ORM\Id]
	#[ORM\Column(type: 'integer', unique: true)]
	#[ORM\GeneratedValue]
	protected int $id;

	#[ORM\Column(type: 'translate')]
	protected Translation $name;

	#[ORM\Column(type: 'translate', nullable: true)]
	protected ?Translation $shortDescription;

	#[ORM\Column(type: 'translate', nullable: true)]
	protected ?Translation $description = null;

	#[ORM\Column(type: 'translate', nullable: true)]
	protected ?Translation $internalNote = null;

	#[ORM\Column(type: 'string', length: 64, unique: true)]
	private string $code;

	#[ORM\Column(type: 'string', length: 64, unique: true, nullable: true)]
	private ?string $ean;

	#[ORM\Column(type: 'string', length: 80, unique: true)]
	private string $slug;

	#[ORM\Column(type: 'integer', unique: true, nullable: true, options: ['unsigned' => true])]
	private ?int $oldId = null;

	#[ORM\ManyToOne(targetEntity: ProductImage::class)]
	private ?ProductImage $mainImage = null;

	#[ORM\ManyToOne(targetEntity: ProductImage::class)]
	private ?ProductImage $secondaryImage = null;

	/** @var Collection<ProductImage> */
	#[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductImage::class)]
	#[ORM\OrderBy(['position' => 'DESC'])]
	private Collection $images;

	/** @var numeric-string */
	#[ORM\Column(type: 'decimal', precision: 15, scale: 4, options: ['unsigned' => true])]
	private string $price;

	/** @var numeric-string|null */
	#[ORM\Column(type: 'decimal', precision: 15, scale: 4, nullable: true, options: ['unsigned' => true])]
	private ?string $standardPricePercentage = null;

	#[ORM\Column(type: 'integer', options: ['unsigned' => true])]
	private int $position = 0;

	#[ORM\Column(type: 'boolean')]
	private bool $active = false;

	#[ORM\Column(type: 'boolean')]
	private bool $showInFeed = true;

	#[ORM\Column(type: 'boolean')]
	private bool $soldOut = true;

	#[ORM\Column(type: 'boolean')]
	private bool $internal = false;

	#[ORM\Column(type: 'boolean')]
	private bool $b2b = false;

	#[ORM\Column(type: 'boolean')]
	private bool $deleted = false;

	/** @var numeric-string|null */
	#[ORM\Column(type: 'decimal', precision: 15, scale: 4, nullable: true, options: ['unsigned' => true])]
	private ?string $vat = null;

	#[ORM\ManyToOne(targetEntity: ProductCategory::class, inversedBy: 'mainProducts')]
	private ?ProductCategory $mainCategory;

	#[ORM\ManyToOne(targetEntity: ProductManufacturer::class)]
	private ?ProductManufacturer $manufacturer = null;

	#[ORM\ManyToOne(targetEntity: Brand::class)]
	private ?Brand $brand = null;

	#[ORM\Column(type: 'float', nullable: true, options: ['unsigned' => true])]
	private ?float $sizeWidth = null;

	#[ORM\Column(type: 'float', nullable: true, options: ['unsigned' => true])]
	private ?float $sizeLength = null;

	#[ORM\Column(type: 'float', nullable: true, options: ['unsigned' => true])]
	private ?float $sizeThickness = null;

	#[ORM\Column(type: 'integer', nullable: true, options: ['unsigned' => true])]
	private ?int $weight = null;

	/** @var Collection<ProductCategory> */
	#[ORM\ManyToMany(targetEntity: ProductCategory::class, inversedBy: 'products')]
	#[ORM\OrderBy(['position' => 'DESC'])]
	private Collection $categories;

	/** @var Collection<ProductLabel> */
	#[ORM\ManyToMany(targetEntity: ProductLabel::class, inversedBy: 'products')]
	private Collection $labels;

	/** @var Collection<ProductSmartDescription> */
	#[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductSmartDescription::class)]
	#[ORM\OrderBy(['position' => 'ASC'])]
	private Collection $smartDescriptions;

	/** @var Collection<ProductParameter> */
	#[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductParameter::class)]
	private Collection $parameters;

	/** @var Collection<ProductVariant> */
	#[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductVariant::class)]
	private Collection $variants;

	/** @var Collection<RelatedProduct> */
	#[ORM\OneToMany(mappedBy: 'product', targetEntity: RelatedProduct::class)]
	private Collection $productRelatedBasic;

	/** @var Collection<RelatedProduct> */
	#[ORM\OneToMany(mappedBy: 'relatedProduct', targetEntity: RelatedProduct::class)]
	private Collection $productRelatedRelated;

	/** @var Collection<ProductSeason> */
	#[ORM\ManyToMany(targetEntity: ProductSeason::class, inversedBy: 'products')]
	private Collection $productSeasons;

	/** @var Collection<ProductTag> */
	#[ORM\ManyToMany(targetEntity: ProductTag::class, inversedBy: 'products')]
	private Collection $tags;

	/** Total available quantity of this product in all warehouses. */
	#[ORM\Column(type: 'integer')]
	private int $warehouseAllQuantity = 0;


	/**
	 * @param numeric-string $price
	 */
	public function __construct(string $name, string $code, string $price)
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
		$this->productSeasons = new ArrayCollection;
		$this->tags = new ArrayCollection;
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getLabel(): string
	{
		return (string) $this->getName();
	}


	public function isVariantProduct(): bool
	{
		return $this->variants->count() > 0;
	}


	/**
	 * @return array<int, array{id: int, name: string, slug: string}>
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
				'id' => $category->getId(),
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
		$this->code = Strings::webalize($code, '_/-.');
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
					sprintf('EAN "%s" is not valid. Please read EAN-13 specification.', $ean)
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
		$slug = trim(Strings::webalize($slug, '/'), '/');
		if ($slug === '') {
			$slug = $this->slug ?: Strings::webalize((string) $this->getName(), '/');
		}
		if ($slug === '') {
			throw new \InvalidArgumentException('Product slug and product name can not be empty.');
		}
		$this->slug = $slug;
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


	public function getSecondaryImage(): ?ProductImage
	{
		return $this->secondaryImage;
	}


	public function setSecondaryImage(?ProductImage $secondaryImage): void
	{
		$this->secondaryImage = $secondaryImage;
	}


	public function getPrice(): string
	{
		return Price::normalize($this->price);
	}


	/**
	 * @param numeric-string $price
	 */
	public function setPrice(string $price): void
	{
		$this->price = $price;
	}


	public function isSale(): bool
	{
		return $this->standardPricePercentage !== null;
	}


	public function getStandardPrice(): string
	{
		if ($this->standardPricePercentage !== null) {
			return bcmul(
				bcdiv($this->standardPricePercentage, '100'),
				$this->getPrice(),
			);
		}

		return $this->getPrice();
	}


	public function getSalePrice(): string
	{
		$return = $this->isSale()
			? bcsub($this->getPrice(), $this->getStandardPrice())
			: $this->getPrice();

		return $return < 0 ? '0' : Price::normalize($return);
	}


	/**
	 * @return numeric-string|null
	 */
	public function getStandardPricePercentage(): ?string
	{
		if ($this->standardPricePercentage !== null) {
			return Price::normalize($this->standardPricePercentage);
		}

		return null;
	}


	/**
	 * @param numeric-string|null $value
	 */
	public function setStandardPricePercentage(?string $value): void
	{
		if ($value !== null) {
			$value = Price::normalize($value);
			if ($value === '0' || ((float) $value) < 0) {
				$value = null;
			}
		}
		$this->standardPricePercentage = $value;
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


	public function isInternal(): bool
	{
		return $this->internal;
	}


	public function setInternal(bool $internal): void
	{
		$this->internal = $internal;
	}


	public function isB2b(): bool
	{
		return $this->b2b;
	}


	public function setB2b(bool $b2b): void
	{
		$this->b2b = $b2b;
	}


	public function isDeleted(): bool
	{
		return $this->deleted;
	}


	public function setDeleted(bool $deleted): void
	{
		$this->deleted = $deleted;
	}


	public function getMainCategory(): ?ProductCategory
	{
		return $this->mainCategory;
	}


	public function setMainCategory(?ProductCategory $mainCategory): void
	{
		$this->mainCategory = $mainCategory;
	}


	public function getManufacturer(): ?ProductManufacturer
	{
		return $this->manufacturer;
	}


	public function setManufacturer(?ProductManufacturer $manufacturer): void
	{
		$this->manufacturer = $manufacturer;
	}


	public function getBrand(): ?Brand
	{
		return $this->brand;
	}


	public function setBrand(?Brand $brand): void
	{
		$this->brand = $brand;
	}


	/**
	 * @param numeric-string $default
	 * @return numeric-string
	 */
	public function getVat(string $default = '21'): string
	{
		return Price::normalize($this->vat ?? $default);
	}


	/**
	 * @param numeric-string|null $vat
	 */
	public function setVat(?string $vat): void
	{
		$floatVal = (float) $vat;
		if ($vat !== null && $floatVal < 0) {
			$vat = '0';
		}
		$this->vat = $vat;
	}


	/**
	 * @return array<int, ProductCategory>
	 */
	public function getCategories(): array
	{
		return $this->categories->toArray();
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
	 * @return Collection&iterable<ProductLabel>
	 */
	public function getLabels(): Collection
	{
		return $this->labels;
	}


	public function addLabel(ProductLabel $label): void
	{
		$this->labels[] = $label;
	}


	/**
	 * @return Collection&iterable<ProductSmartDescription>
	 */
	public function getSmartDescriptions(): Collection
	{
		return $this->smartDescriptions;
	}


	/**
	 * @return Collection&iterable<ProductImage>
	 */
	public function getImages(): Collection
	{
		return $this->images;
	}


	/**
	 * @return Collection&iterable<ProductParameter>
	 */
	public function getParameters(): Collection
	{
		return $this->parameters;
	}


	/**
	 * @return Collection&iterable<ProductVariant>
	 */
	public function getVariants(): Collection
	{
		return $this->variants;
	}


	public function getMaximalSize(): ?float
	{
		$max = max([$this->sizeWidth ?? -1.0, $this->sizeLength ?? -1.0, $this->sizeThickness ?? -1.0]);

		return $max > 0 ? $max : null;
	}


	public function getMinimalSize(): ?float
	{
		$min = min([$this->sizeWidth ?? -1.0, $this->sizeLength ?? -1.0, $this->sizeThickness ?? -1.0]);

		return $min > 0 ? $min : null;
	}


	public function getSizeWidth(): ?float
	{
		return $this->sizeWidth;
	}


	public function setSizeWidth(?float $value): void
	{
		if ($value !== null && $value <= 0) {
			throw new \InvalidArgumentException(sprintf('Size can not be negative, but "%s" given.', $value));
		}
		$this->sizeWidth = $value;
	}


	public function getSizeLength(): ?float
	{
		return $this->sizeLength;
	}


	public function setSizeLength(?float $value): void
	{
		if ($value !== null && $value <= 0) {
			throw new \InvalidArgumentException(sprintf('Size can not be negative, but "%s" given.', $value));
		}
		$this->sizeLength = $value;
	}


	public function getSizeThickness(): ?float
	{
		return $this->sizeThickness;
	}


	public function setSizeThickness(?float $value): void
	{
		if ($value !== null && $value <= 0) {
			throw new \InvalidArgumentException(sprintf('Size can not be negative, but "%s" given.', $value));
		}
		$this->sizeThickness = $value;
	}


	public function getWeight(): ?int
	{
		return $this->weight;
	}


	public function setWeight(?int $value): void
	{
		if ($value !== null && $value <= 0) {
			throw new \InvalidArgumentException(sprintf('Weight can not be negative, but "%s" given.', $value));
		}
		$this->weight = $value;
	}


	/**
	 * @return Collection&iterable<RelatedProduct>
	 */
	public function getProductRelatedBasic(): Collection
	{
		return $this->productRelatedBasic;
	}


	/**
	 * @return Collection&iterable<RelatedProduct>
	 */
	public function getProductRelatedRelated(): Collection
	{
		return $this->productRelatedRelated;
	}


	/**
	 * @return Collection<ProductSeason>
	 */
	public function getProductSeasons(): Collection
	{
		return $this->productSeasons;
	}


	public function addProductSeason(ProductSeason $productSeason): void
	{
		$this->productSeasons[] = $productSeason;
	}


	/**
	 * @return array<int, ProductTag>
	 */
	public function getTags(): array
	{
		return $this->tags->toArray();
	}


	public function addTag(ProductTag $tag): void
	{
		$this->tags[] = $tag;
	}


	/**
	 * @param array<int, ProductSeason> $seasons
	 */
	public function setSeasonList(array $seasons): void
	{
		$keepIds = array_map(static fn(ProductSeason $season): int => $season->getId(), $seasons);
		$checkedIds = [];

		foreach ($this->productSeasons as $key => $season) {
			if (in_array($season->getId(), $keepIds, true) === false) {
				$season->removeProduct($this);
				$this->productSeasons->remove($key);
			}
			$checkedIds[] = $season->getId();
		}
		foreach ($seasons as $season) {
			if (in_array($season->getId(), $checkedIds, true) === false) {
				$season->addProduct($this);
				$this->addProductSeason($season);
			}
		}
	}


	/**
	 * @param array<int, ProductTag> $tags
	 */
	public function setTagList(array $tags): void
	{
		$keepIds = array_map(static fn(ProductTag $tag): int => $tag->getId(), $tags);
		$checkedIds = [];

		foreach ($this->tags as $key => $tag) {
			if (in_array($tag->getId(), $keepIds, true) === false) {
				$tag->removeProduct($this);
				$this->tags->remove($key);
			}
			$checkedIds[] = $tag->getId();
		}
		foreach ($tags as $tag) {
			if (in_array($tag->getId(), $checkedIds, true) === false) {
				$tag->addProduct($this);
				$this->addTag($tag);
			}
		}
	}


	public function getWarehouseAllQuantity(): int
	{
		return $this->warehouseAllQuantity;
	}


	public function setWarehouseAllQuantity(int $warehouseAllQuantity): void
	{
		$this->warehouseAllQuantity = $warehouseAllQuantity;
	}
}
