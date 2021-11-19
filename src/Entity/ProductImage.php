<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Baraja\Doctrine\Identifier\IdentifierUnsigned;
use Baraja\Localization\TranslateObject;
use Baraja\Localization\Translation;
use Baraja\Url\Url;
use Doctrine\ORM\Mapping as ORM;

/**
 * @method Translation|null getTitle(?string $locale = null)
 * @method void setTitle(?string $content = null, ?string $locale = null)
 */
#[ORM\Entity]
#[ORM\Table(name: 'shop__product_image')]
class ProductImage
{
	use IdentifierUnsigned;
	use TranslateObject;

	#[ORM\ManyToOne(targetEntity: Product::class, cascade: ['persist'], inversedBy: 'images')]
	#[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
	private Product $product;

	#[ORM\ManyToOne(targetEntity: ProductVariant::class, cascade: ['persist'])]
	#[ORM\JoinColumn(name: 'variant_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
	private ?ProductVariant $variant;

	#[ORM\Column(type: 'string')]
	private string $source;

	#[ORM\Column(type: 'translate', nullable: true)]
	private ?Translation $title = null;

	#[ORM\Column(type: 'integer', options: ['unsigned' => true])]
	private int $position = 0;

	#[ORM\Column(type: 'string', length: 32, nullable: true)]
	private ?string $tag = null;


	public function __construct(Product $product, string $source, ?string $title = null)
	{
		$this->product = $product;
		$this->source = trim($source, '/');
		$this->setTitle($title);
	}


	/**
	 * @return array{source: string, title: string, url: string}
	 */
	public function toArray(): array
	{
		return [
			'source' => $this->source,
			'title' => $this->getAltTitle(),
			'url' => $this->getUrl(),
		];
	}


	public function __toString(): string
	{
		return $this->getUrl();
	}


	public function getUrl(): string
	{
		return Url::get()->getBaseUrl() . '/' . $this->getRelativePath();
	}


	public function getProduct(): Product
	{
		return $this->product;
	}


	public function getVariant(): ?ProductVariant
	{
		return $this->variant;
	}


	public function setVariant(?ProductVariant $variant): void
	{
		$this->variant = $variant;
	}


	public function getRelativePath(): string
	{
		return 'product-image/' . $this->source;
	}


	public function getSource(): string
	{
		return $this->source;
	}


	public function getAltTitle(): string
	{
		return ((string) $this->getTitle()) ?: (string) $this->product->getName();
	}


	public function getPosition(): int
	{
		return $this->position;
	}


	public function setPosition(int $position): void
	{
		$this->position = $position;
	}


	public function getTag(): ?string
	{
		return $this->tag;
	}


	public function setTag(?string $tag): void
	{
		$this->tag = $tag;
	}
}
