<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\FileSystem;


use Baraja\Shop\Product\Entity\ProductImage;
use Nette\Utils\FileSystem;

class ProductImageFileSystem implements ProductFileSystem
{
	private string $wwwDir;


	public function __construct(?string $wwwDir = null)
	{
		$this->wwwDir = $wwwDir ?? $this->detectWwwDir();
	}


	public function save(ProductImage $productImage, string $tempPath): void
	{
		FileSystem::copy($tempPath, $this->getAbsoluteDiskPathByProductImage($productImage));
		FileSystem::delete($tempPath);
	}


	public function delete(ProductImage $productImage): void
	{
		FileSystem::delete($this->getAbsoluteDiskPathByProductImage($productImage));
	}


	private function detectWwwDir(): string
	{
		$scriptFileName = $_SERVER['SCRIPT_FILENAME'] ?? null;
		if ($scriptFileName === null) {
			throw new \LogicException('PHP configuration option "SCRIPT_FILENAME" does not exist.');
		}
		if (is_file($scriptFileName) === false) {
			throw new \LogicException(sprintf('Root filename "%s" does not exist.', $scriptFileName));
		}

		return dirname($scriptFileName);
	}


	private function getAbsoluteDiskPathByProductImage(ProductImage $productImage): string
	{
		return sprintf('%s/%s', $this->wwwDir, $productImage->getRelativePath());
	}
}
