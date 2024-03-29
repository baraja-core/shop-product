<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Baraja\EcommerceStandard\DTO\ImageInterface;
use Baraja\ImageGenerator\ImageGenerator;
use Baraja\Localization\TranslateObject;
use Baraja\Localization\Translation;
use Baraja\Shop\Product\Repository\ProductImageRepository;
use Baraja\Url\Url;
use Doctrine\ORM\Mapping as ORM;

/**
 * @method Translation|null getTitle(?string $locale = null)
 * @method void setTitle(?string $content = null, ?string $locale = null)
 */
#[ORM\Entity(repositoryClass: ProductImageRepository::class)]
#[ORM\Table(name: 'shop__product_image')]
class ProductImage implements ImageInterface
{
	use TranslateObject;

	#[ORM\Id]
	#[ORM\Column(type: 'integer', unique: true, options: ['unsigned' => true])]
	#[ORM\GeneratedValue]
	protected int $id;

	#[ORM\Column(type: 'translate', nullable: true)]
	protected ?Translation $title = null;

	#[ORM\ManyToOne(targetEntity: Product::class, cascade: ['persist'], inversedBy: 'images')]
	#[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
	private Product $product;

	#[ORM\ManyToOne(targetEntity: ProductVariant::class, cascade: ['persist'])]
	#[ORM\JoinColumn(name: 'variant_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
	private ?ProductVariant $variant;

	#[ORM\Column(type: 'string')]
	private string $source;

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


	public function getId(): int
	{
		return $this->id;
	}


	/**
	 * @return array{
	 *     source: string,
	 *     title: string,
	 *     url: string,
	 *     urlBig: string,
	 *     urlThumbnail: string
	 * }
	 */
	public function toArray(): array
	{
		$url = $this->getUrl();

		return [
			'source' => $this->source,
			'title' => $this->getAltTitle(),
			'url' => $url,
			'urlBig' => ImageGenerator::from($url, ['w' => 1024, 'h' => 1024]),
			'urlThumbnail' => ImageGenerator::from($url, ['w' => 200, 'h' => 200]),
		];
	}


	public function __toString(): string
	{
		return $this->getUrl();
	}


	public function getUrl(): string
	{
		return sprintf('%s/%s', Url::get()->getBaseUrl(), $this->getRelativePath());
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
