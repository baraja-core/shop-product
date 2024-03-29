<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Baraja\EcommerceStandard\DTO\CategoryInterface;
use Baraja\Localization\TranslateObject;
use Baraja\Localization\Translation;
use Baraja\Shop\Product\Repository\ProductCategoryRepository;
use Baraja\Url\Url;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nette\Utils\Strings;

/**
 * @method Translation getName(?string $locale = null)
 * @method void setName(string $content, ?string $locale = null)
 * @method Translation|null getDescription(?string $locale = null)
 * @method void setDescription(?string $content = null, ?string $locale = null)
 */
#[ORM\Entity(repositoryClass: ProductCategoryRepository::class)]
#[ORM\Table(name: 'shop__product_category')]
class ProductCategory implements CategoryInterface
{
	use TranslateObject;

	#[ORM\Id]
	#[ORM\Column(type: 'integer', unique: true)]
	#[ORM\GeneratedValue]
	protected int $id;

	#[ORM\Column(type: 'translate')]
	protected Translation $name;

	#[ORM\Column(type: 'translate', nullable: true)]
	protected ?Translation $description = null;

	#[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'child')]
	private ?self $parent = null;

	/** @var Collection<self> */
	#[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
	private Collection $child;

	#[ORM\Column(type: 'string', length: 64, unique: true)]
	private string $code;

	#[ORM\Column(type: 'string', length: 64, unique: true)]
	private string $slug;

	#[ORM\Column(type: 'integer', options: ['unsigned' => true])]
	private int $position = 0;

	#[ORM\Column(type: 'boolean')]
	private bool $active = false;

	#[ORM\Column(type: 'boolean')]
	private bool $internal = false;

	#[ORM\Column(type: 'boolean')]
	private bool $b2b = false;

	#[ORM\Column(type: 'boolean')]
	private bool $deleted = false;

	#[ORM\Column(type: 'integer', nullable: true, options: ['unsigned' => true])]
	private ?int $heurekaCategoryId = null;

	#[ORM\Column(type: 'string', length: 128, nullable: true)]
	private ?string $mainPhotoPath = null;

	#[ORM\Column(type: 'string', length: 128, nullable: true)]
	private ?string $mainThumbnailPath = null;

	/** @var Collection<Product> */
	#[ORM\OneToMany(mappedBy: 'mainCategory', targetEntity: Product::class)]
	private Collection $mainProducts;

	/** @var Collection<Product> */
	#[ORM\ManyToMany(targetEntity: Product::class, mappedBy: 'categories')]
	private Collection $products;


	public function __construct(string $name, string $code, ?string $slug = null)
	{
		$this->setName(Strings::firstUpper($name));
		$this->setCode($code);
		$this->setSlug($slug ?: $code);
		$this->child = new ArrayCollection;
		$this->mainProducts = new ArrayCollection;
		$this->products = new ArrayCollection;
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getLabel(): string
	{
		return (string) $this->getName();
	}


	public function getRootId(): int
	{
		$parent = $this->getParent();
		if ($parent !== null) {
			return $parent->getRootId();
		}

		return $this->getId();
	}


	/**
	 * @return array<int, int>
	 */
	public function getAllChildIds(): array
	{
		$return = [$this->getId()];
		foreach ($this->getChild() as $child) {
			$return[] = $child->getId();
		}

		return array_unique($return);
	}


	/**
	 * @return array<int, string>
	 */
	public function getPath(): array
	{
		$return = [];
		$parent = $this;
		do {
			$return[$parent->getId()] = (string) $parent->getName();
			$parent = $parent->getParent();
		} while ($parent !== null);

		return array_reverse($return, true);
	}


	public function getParent(): ?self
	{
		return $this->parent;
	}


	/**
	 * @return array<int, self>
	 */
	public function getAllParents(): array
	{
		$return = [];
		$parent = $this;
		do {
			$return[] = $parent;
			$parent = $parent->getParent();
		} while ($parent !== null);

		return $return;
	}


	public function setParent(?self $parent): void
	{
		$this->parent = $parent;
	}


	public function getParentId(): ?int
	{
		return $this->parent?->getId();
	}


	/**
	 * @return Collection&iterable<self>
	 */
	public function getChild(): Collection
	{
		return $this->child;
	}


	/**
	 * @return array<int, self>
	 */
	public function getAllChildren(): array
	{
		$return = [];
		foreach ($this->getChild() as $category) {
			$return[] = [$category];
			$return[] = $category->getAllChildren();
		}

		return array_merge([], ...$return);
	}


	public function getCode(): string
	{
		return $this->code;
	}


	public function setCode(string $code): void
	{
		$code = Strings::webalize($code);
		if ($code === '') {
			$code = $this->getSlug();
		}
		$this->code = $code;
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
			throw new \InvalidArgumentException('Product category slug and product name can not be empty.');
		}
		$this->slug = $slug;
	}


	public function getPosition(): int
	{
		return $this->position;
	}


	public function setPosition(int $position): void
	{
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


	public function getHeurekaCategoryId(): ?int
	{
		return $this->heurekaCategoryId;
	}


	public function setHeurekaCategoryId(?int $heurekaCategoryId): void
	{
		$this->heurekaCategoryId = $heurekaCategoryId;
	}


	public function getMainPhotoUrl(): ?string
	{
		return $this->mainPhotoPath !== null
			? sprintf('%s/%s', Url::get()->getBaseUrl(), $this->mainPhotoPath)
			: null;
	}


	public function getMainPhotoPath(): ?string
	{
		return $this->mainPhotoPath;
	}


	public function setMainPhotoPath(?string $mainPhotoPath): void
	{
		$this->mainPhotoPath = $mainPhotoPath;
	}


	public function getMainThumbnailUrl(): ?string
	{
		return $this->mainThumbnailPath !== null
			? sprintf('%s/%s', Url::get()->getBaseUrl(), $this->mainThumbnailPath)
			: null;
	}


	public function getMainThumbnailPath(): ?string
	{
		return $this->mainThumbnailPath;
	}


	public function setMainThumbnailPath(?string $mainThumbnailPath): void
	{
		$this->mainThumbnailPath = $mainThumbnailPath;
	}


	/**
	 * @return array<int, Product>
	 */
	public function getMainProducts(): array
	{
		return $this->mainProducts->toArray();
	}


	/**
	 * @return array<int, Product>
	 */
	public function getProducts(): array
	{
		return $this->products->toArray();
	}
}
