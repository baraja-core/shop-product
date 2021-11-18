<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\FileSystem;


use Baraja\Shop\Product\Entity\ProductImage;
use Nette\Utils\FileSystem;

class ProductImageFileSystem implements ProductFileSystem
{
	private string $wwwDir;


	public function __construct(string $wwwDir)
	{
		$this->wwwDir = $wwwDir;
	}


	public function save(ProductImage $productImage, string $tempPath): void
	{
		$diskPath = $this->wwwDir . '/' . $productImage->getRelativePath();
		FileSystem::copy($tempPath, $diskPath);
		FileSystem::delete($tempPath);
	}


	public function delete(ProductImage $productImage): void
	{
		FileSystem::delete($this->wwwDir . '/' . $productImage->getRelativePath());
	}
}
