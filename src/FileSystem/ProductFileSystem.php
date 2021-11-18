<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\FileSystem;


use Baraja\Shop\Product\Entity\ProductImage;

interface ProductFileSystem
{
	public function save(ProductImage $productImage, string $tempPath): void;

	public function delete(ProductImage $productImage): void;
}
