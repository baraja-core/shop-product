<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Baraja\Shop\Product\Repository\ProductCollectionItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductCollectionItemRepository::class)]
#[ORM\Table(name: 'shop__product_collection_item')]
class ProductCollectionItem
{
	#[ORM\Id]
	#[ORM\Column(type: 'integer', unique: true)]
	#[ORM\GeneratedValue]
	protected int $id;

	#[ORM\ManyToOne(targetEntity: Product::class)]
	private Product $baseProduct;

	#[ORM\ManyToOne(targetEntity: Product::class)]
	private Product $relevantProduct;

	#[ORM\ManyToOne(targetEntity: ProductVariant::class)]
	private ?ProductVariant $relevantProductVariant;

	#[ORM\Column(type: 'integer')]
	private int $position = 0;


	public function __construct(
		Product $baseProduct,
		Product $relevantProduct,
		?ProductVariant $relevantProductVariant,
	) {
		if ($baseProduct->getId() === $relevantProduct->getId()) {
			throw new \InvalidArgumentException(
				sprintf(
					'Collection base product "%d" can not be part of collection item.',
					$baseProduct->getId(),
				),
			);
		}
		if (
			$relevantProductVariant !== null
			&& $relevantProductVariant->getProduct()->getId() !== $relevantProduct->getId()
		) {
			throw new \InvalidArgumentException(
				sprintf(
					'Product variant "%d" (from product "%s") is not compatible with product "%d" ("%s").',
					$relevantProductVariant->getId(),
					$relevantProductVariant->getProduct()->getLabel(),
					$relevantProduct->getId(),
					$relevantProduct->getLabel(),
				),
			);
		}

		$this->baseProduct = $baseProduct;
		$this->relevantProduct = $relevantProduct;
		$this->relevantProductVariant = $relevantProductVariant;
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getBaseProduct(): Product
	{
		return $this->baseProduct;
	}


	public function getRelevantProduct(): Product
	{
		return $this->relevantProduct;
	}


	public function getRelevantProductVariant(): ?ProductVariant
	{
		return $this->relevantProductVariant;
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
		$this->position = $position;
	}
}
