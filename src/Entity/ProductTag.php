<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Baraja\EcommerceStandard\DTO\ProductInterface;
use Baraja\EcommerceStandard\DTO\ProductTagInterface;
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
#[ORM\Table(name: 'shop__product_tag')]
class ProductTag implements ProductTagInterface
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

	#[ORM\Column(type: 'string', length: 128, unique: true)]
	private string $slug;

	#[ORM\Column(type: 'string', length: 128, nullable: true)]
	private ?string $imageUrl = null;

	#[ORM\Column(type: 'string', length: 7, nullable: true)]
	private ?string $color = null;

	#[ORM\Column(type: 'boolean')]
	private bool $showOnCard = false;

	#[ORM\Column(type: 'boolean')]
	private bool $freeDelivery = false;

	/** @var Collection<Product> */
	#[ORM\ManyToMany(targetEntity: Product::class, mappedBy: 'tags')]
	private Collection $products;


	public function __construct(string $name)
	{
		$this->setName(Strings::firstUpper($name));
		$this->setSlug($name);
		$this->products = new ArrayCollection;
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getSlug(): string
	{
		return $this->slug;
	}


	public function setSlug(string $slug): void
	{
		$this->slug = Strings::webalize($slug);
	}


	public function getImageUrl(): ?string
	{
		return $this->imageUrl;
	}


	public function setImageUrl(?string $imageUrl): void
	{
		$this->imageUrl = $imageUrl;
	}


	public function getColor(): ?string
	{
		return $this->color;
	}


	public function setColor(?string $color): void
	{
		$this->color = $color;
	}


	public function isShowOnCard(): bool
	{
		return $this->showOnCard;
	}


	public function setShowOnCard(bool $showOnCard): void
	{
		$this->showOnCard = $showOnCard;
	}


	public function isFreeDelivery(): bool
	{
		return $this->freeDelivery;
	}


	public function setFreeDelivery(bool $freeDelivery): void
	{
		$this->freeDelivery = $freeDelivery;
	}


	/**
	 * @return Collection<Product>
	 */
	public function getProducts(): Collection
	{
		return $this->products;
	}


	public function addProduct(ProductInterface $product): void
	{
		assert($product instanceof Product);
		$this->products[] = $product;
	}


	public function removeProduct(ProductInterface $product): void
	{
		foreach ($this->products as $key => $productItem) {
			if ($productItem->getId() === $product->getId()) {
				$this->products->remove($key);
			}
		}
	}
}
