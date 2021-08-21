<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Baraja\Doctrine\Identifier\Identifier;
use Baraja\Localization\TranslateObject;
use Baraja\Localization\Translation;
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
#[ORM\Entity]
#[ORM\Table(name: 'shop__product_category')]
class ProductCategory
{
	use Identifier;
	use TranslateObject;

	#[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'child')]
	private ?self $parent = null;

	/** @var self[]|Collection */
	#[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
	private $child;

	#[ORM\Column(type: 'translate')]
	private Translation $name;

	#[ORM\Column(type: 'string', length: 64, unique: true)]
	private string $code;

	#[ORM\Column(type: 'string', length: 64, unique: true)]
	private string $slug;

	#[ORM\Column(type: 'translate', nullable: true)]
	private ?Translation $description = null;

	#[ORM\Column(type: 'integer', options: ['unsigned' => true])]
	private int $position = 0;

	#[ORM\Column(type: 'boolean')]
	private bool $active = false;

	#[ORM\Column(type: 'integer', nullable: true, options: ['unsigned' => true])]
	private ?int $heurekaCategoryId = null;

	/** @var Product[]|Collection */
	#[ORM\OneToMany(mappedBy: 'mainCategory', targetEntity: Product::class)]
	private $mainProducts;

	/** @var Product[]|Collection */
	#[ORM\ManyToMany(targetEntity: Product::class, mappedBy: 'categories')]
	private $products;


	public function __construct(string $name, string $code, ?string $slug = null)
	{
		$this->setName(Strings::firstUpper($name));
		$this->code = Strings::webalize($code);
		$this->setSlug($slug ?: $code);
		$this->child = new ArrayCollection;
		$this->mainProducts = new ArrayCollection;
		$this->products = new ArrayCollection;
	}


	public function getRootId(): int
	{
		$parent = $this->getParent();
		if ($parent !== null) {
			return $parent->getRootId();
		}
		$id = $this->getId();
		if (is_int($id) === false) {
			throw new \LogicException('Entity "' . static::class . '" must be flushed first.');
		}

		return $id;
	}


	/**
	 * @return array<int, int>
	 */
	public function getAllChildIds(): array
	{
		$return = [(int) $this->getId()];
		foreach ($this->getChild() as $child) {
			$childId = $child->getId();
			if (is_int($childId) === false) {
				throw new \LogicException(
					'Entity "' . static::class . '" child ID does not exist, '
					. 'because related entities must be flushed first.',
				);
			}
			$return[] = $childId;
		}

		return array_unique($return);
	}


	public function getParent(): ?self
	{
		return $this->parent;
	}


	public function setParent(?self $parent): void
	{
		$this->parent = $parent;
	}


	/**
	 * @return self[]|Collection
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
	public function getMainProducts()
	{
		return $this->mainProducts;
	}


	/**
	 * @return Product[]|Collection
	 */
	public function getProducts()
	{
		return $this->products;
	}
}
