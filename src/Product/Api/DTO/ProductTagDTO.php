<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Api\DTO;


use Baraja\EcommerceStandard\DTO\ProductTagInterface;

final class ProductTagDTO
{
	public function __construct(
		public string $slug,
		public string $label,
		public ?string $color,
		public bool $freeDelivery,
	) {
	}


	public static function fromEntity(ProductTagInterface $tag): self
	{
		return new self(
			slug: $tag->getSlug(),
			label: $tag->getLabel(),
			color: $tag->getColor(),
			freeDelivery: $tag->isFreeDelivery(),
		);
	}
}
