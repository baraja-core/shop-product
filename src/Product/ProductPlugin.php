<?php

declare(strict_types=1);

namespace Baraja\Shop\Product;


use Baraja\Cms\Search\SearchablePlugin;
use Baraja\Plugin\BasePlugin;
use Baraja\Plugin\SimpleComponent\Button;
use Baraja\Shop\Product\Entity\Product;
use Baraja\Shop\Product\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\InvalidLinkException;

final class ProductPlugin extends BasePlugin implements SearchablePlugin
{
	private ProductRepository $productRepository;


	public function __construct(
		private LinkGenerator $linkGenerator,
		EntityManagerInterface $entityManager,
	) {
		$productRepository = $entityManager->getRepository(Product::class);
		assert($productRepository instanceof ProductRepository);
		$this->productRepository = $productRepository;
	}


	/**
	 * @return class-string<Product>
	 */
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
		return 'Products';
	}


	public function actionDetail(int $id): void
	{
		try {
			$product = $this->productRepository->getById($id);
			$this->setTitle(sprintf('(%d) %s', $id, $product->getLabel()));
		} catch (NoResultException | NonUniqueResultException) {
			$this->error(sprintf('Product "%s" doest not exist.', $id));
		}

		try {
			$webLink = $this->linkGenerator->link(
				'Front:Product:detail',
				[
					'slug' => $product->getSlug(),
				],
			);
			$this->addButton(new Button(Button::VARIANT_INFO, 'Web', Button::ACTION_LINK_TARGET, $webLink));
		} catch (InvalidLinkException) {
			// Silence is golden.
		}
		$this->addButton(
			new Button(
				variant: Button::VARIANT_SECONDARY,
				label: 'Clone',
				action: Button::ACTION_MODAL,
				target: 'modal-clone-product',
			),
		);
	}
}
