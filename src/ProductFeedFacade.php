<?php

declare(strict_types=1);

namespace Baraja\Shop\Product;


use Baraja\ImageGenerator\ImageGenerator;
use Baraja\Markdown\CommonMarkRenderer;
use Baraja\Search\Search;
use Baraja\Shop\Currency\CurrencyManagerAccessor;
use Baraja\Shop\Price\Price;
use Baraja\Shop\Product\Entity\Product;
use Baraja\Shop\Product\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Paginator;
use Nette\Utils\Strings;

final class ProductFeedFacade
{
	private ProductRepository $productRepository;


	public function __construct(
		EntityManagerInterface $entityManager,
		private CurrencyManagerAccessor $currencyManager,
		private Search $search,
		private CommonMarkRenderer $renderer,
	) {
		$productRepository = $entityManager->getRepository(Product::class);
		assert($productRepository instanceof ProductRepository);
		$this->productRepository = $productRepository;
	}


	/**
	 * @return array{
	 *     count: int,
	 *     items: array<int, array<string, mixed>>,
	 *     paginator: Paginator
	 * }
	 */
	public function getFeed(?string $query = null, int $page = 1, int $limit = 32): array
	{
		$mainCurrency = $this->currencyManager->get()->getMainCurrency();

		if ($query !== null && $query !== '') {
			$searchIds = $this->search->search(
				$query,
				[
					Product::class => [
						'name',
						'code',
						'ean',
						'shortDescription',
						'price',
						'smartDescriptions.description',
					],
				],
				useAnalytics: false,
			)->getIds();
		} else {
			$searchIds = [];
		}

		$selection = $this->productRepository->getFeedCandidates($searchIds);

		$count = (int) (clone $selection)->select('COUNT(product.id)')
			->getQuery()
			->getSingleScalarResult();

		$items = $selection->orderBy('product.active', 'DESC')
			->addOrderBy('product.position', 'DESC')
			->setMaxResults($limit)
			->setFirstResult(($page - 1) * $limit)
			->getQuery()
			->getArrayResult();

		$return = [];
		foreach ($items as $item) {
			$item['priceRender'] = (new Price($item['price'], $mainCurrency))->render(true);
			$mainImage = $item['mainImage'];
			if ($mainImage !== null) {
				$item['mainImage']['source'] = ImageGenerator::from($mainImage['source'], ['w' => 200, 'h' => 200]);
				$item['shortDescription'] = Strings::truncate(
					strip_tags($this->renderer->render($item['shortDescription'])),
					128,
				);
			}
			$return[] = $item;
		}

		return [
			'count' => $count,
			'items' => $return,
			'paginator' => (new Paginator)
				->setItemCount($count)
				->setItemsPerPage($limit)
				->setPage($page),
		];
	}
}
