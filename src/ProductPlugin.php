<?php

declare(strict_types=1);

namespace Baraja\Shop\Product;


use Baraja\Cms\Search\SearchablePlugin;
use Baraja\Doctrine\EntityManager;
use Baraja\Plugin\BasePlugin;
use Baraja\Plugin\SimpleComponent\Button;
use Baraja\Shop\Product\Entity\Product;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Nette\Application\LinkGenerator;

final class ProductPlugin extends BasePlugin implements SearchablePlugin
{
	public function __construct(
		private EntityManager $entityManager,
		private LinkGenerator $linkGenerator
	) {
	}


	public function getBaseEntity(): string
	{
		return Product::class;
	}


	public function getSearchColumns(): array
	{
		return [':name', 'code', 'ean', 'shortDescription'];
	}


	public function getName(): string
	{
		return 'Product';
	}


	public function getLabel(): string
	{
		return 'Produkty';
	}


	public function actionDetail(int $id): void
	{
		try {
			/** @var Product $product */
			$product = $this->entityManager->getRepository(Product::class)
				->createQueryBuilder('product')
				->where('product.id = :id')
				->setParameter('id', $id)
				->setMaxResults(1)
				->getQuery()
				->getSingleResult();

			$this->setTitle((string) $product->getName());
		} catch (NoResultException | NonUniqueResultException) {
			$this->error('Product "' . $id . '" doest not exist.');
		}

		$link = $this->linkGenerator->link(
			'Front:Product:detail',
			[
				'slug' => $product->getSlug(),
			],
		);
		$this->addButton(new Button(Button::VARIANT_INFO, 'Web', Button::ACTION_LINK_TARGET, $link));
	}
}