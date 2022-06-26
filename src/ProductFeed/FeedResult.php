<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\ProductFeed;


use Baraja\Shop\Product\Entity\Product;
use Nette\Utils\Paginator;

final class FeedResult implements \Iterator
{
	private int $key = 0;


	/**
	 * @param array<int, Product> $products
	 * @param array<int, int> $pages
	 */
	public function __construct(
		private array $products,
		private FeedStatistic $statistic,
		private Paginator $paginator,
		private array $pages = [],
	) {
	}


	/**
	 * @return array<int, Product>
	 */
	public function getProducts(): array
	{
		return $this->products;
	}


	public function getStatistic(): FeedStatistic
	{
		return $this->statistic;
	}


	public function getPaginator(): Paginator
	{
		return $this->paginator;
	}


	/**
	 * @return array<int, int>
	 */
	public function getPages(): array
	{
		return $this->pages;
	}


	public function current(): ?Product
	{
		return $this->products[$this->key] ?? null;
	}


	public function next(): void
	{
		$this->key++;
	}


	public function key(): int
	{
		return $this->key;
	}


	public function valid(): bool
	{
		return isset($this->products[$this->key]);
	}


	public function rewind(): void
	{
		$this->key = 0;
	}
}
