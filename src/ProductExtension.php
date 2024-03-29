<?php

declare(strict_types=1);

namespace Baraja\Shop\Product;


use Baraja\Doctrine\ORM\DI\OrmAnnotationsExtension;
use Baraja\Plugin\Component\VueComponent;
use Baraja\Plugin\PluginComponentExtension;
use Baraja\Plugin\PluginManager;
use Baraja\Shop\Product\Availability\ProductWarehouseStatus;
use Baraja\Shop\Product\Category\ProductCategoryManager;
use Baraja\Shop\Product\Category\ProductCategoryManagerAccessor;
use Baraja\Shop\Product\Category\ProductCategoryPlugin;
use Baraja\Shop\Product\ProductFeed\Feed;
use Baraja\Shop\Product\Recommender\ProductRecommender;
use Baraja\Shop\Product\Recommender\ProductRecommenderAccessor;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;

final class ProductExtension extends CompilerExtension
{
	/**
	 * @return array<int, string>
	 */
	public static function mustBeDefinedBefore(): array
	{
		return [OrmAnnotationsExtension::class];
	}


	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		PluginComponentExtension::defineBasicServices($builder);
		OrmAnnotationsExtension::addAnnotationPathToManager($builder, 'Baraja\Shop\Product\Entity', __DIR__ . '/Entity');

		$builder->addDefinition($this->prefix('productManager'))
			->setFactory(ProductManager::class);

		$builder->addDefinition($this->prefix('productFeedFacade'))
			->setFactory(ProductFeedFacade::class);

		$builder->addAccessorDefinition($this->prefix('productManagerAccessor'))
			->setImplement(ProductManagerAccessor::class);

		$builder->addDefinition($this->prefix('productRecommender'))
			->setFactory(ProductRecommender::class);

		$builder->addAccessorDefinition($this->prefix('productRecommenderAccessor'))
			->setImplement(ProductRecommenderAccessor::class);

		$builder->addDefinition($this->prefix('productCategoryManager'))
			->setFactory(ProductCategoryManager::class);

		$builder->addAccessorDefinition($this->prefix('productCategoryManagerAccessor'))
			->setImplement(ProductCategoryManagerAccessor::class);

		$builder->addDefinition($this->prefix('productFieldManager'))
			->setFactory(ProductFieldManager::class);

		$builder->addDefinition($this->prefix('productPriceManager'))
			->setFactory(ProductPriceManager::class);

		$builder->addDefinition($this->prefix('productWarehouseStatus'))
			->setFactory(ProductWarehouseStatus::class);

		$builder->addDefinition($this->prefix('feed'))
			->setFactory(Feed::class);

		$builder->addDefinition($this->prefix('productCombinationFilter'))
			->setFactory(ProductCombinationFilter::class);

		/** @var ServiceDefinition $pluginManager */
		$pluginManager = $this->getContainerBuilder()->getDefinitionByType(PluginManager::class);
		$pluginManager->addSetup('?->addComponent(?)', ['@self', [
			'key' => 'productDefault',
			'name' => 'cms-product-default',
			'implements' => ProductPlugin::class,
			'componentClass' => VueComponent::class,
			'view' => 'default',
			'source' => __DIR__ . '/../templates/product/default.js',
			'position' => 100,
			'tab' => 'Products',
			'params' => [],
		]]);
		$pluginManager->addSetup('?->addComponent(?)', ['@self', [
			'key' => 'productOverview',
			'name' => 'cms-product-overview',
			'implements' => ProductPlugin::class,
			'componentClass' => VueComponent::class,
			'view' => 'detail',
			'source' => __DIR__ . '/../templates/product/overview.js',
			'position' => 100,
			'tab' => 'Overview',
			'params' => ['id'],
		]]);
		$pluginManager->addSetup('?->addComponent(?)', ['@self', [
			'key' => 'productCategory',
			'name' => 'cms-product-category',
			'implements' => ProductPlugin::class,
			'componentClass' => VueComponent::class,
			'view' => 'detail',
			'source' => __DIR__ . '/../templates/product/category.js',
			'position' => 80,
			'tab' => 'Category',
			'params' => ['id'],
		]]);
		$pluginManager->addSetup('?->addComponent(?)', ['@self', [
			'key' => 'productStock',
			'name' => 'cms-product-stock',
			'implements' => ProductPlugin::class,
			'componentClass' => VueComponent::class,
			'view' => 'detail',
			'source' => __DIR__ . '/../templates/product/stock.js',
			'position' => 70,
			'tab' => 'Stock',
			'params' => ['id'],
		]]);
		$pluginManager->addSetup('?->addComponent(?)', ['@self', [
			'key' => 'productMedia',
			'name' => 'cms-product-media',
			'implements' => ProductPlugin::class,
			'componentClass' => VueComponent::class,
			'view' => 'detail',
			'source' => __DIR__ . '/../templates/product/media.js',
			'position' => 60,
			'tab' => 'Media',
			'params' => ['id'],
		]]);
		$pluginManager->addSetup('?->addComponent(?)', ['@self', [
			'key' => 'productPriceList',
			'name' => 'cms-product-price-list',
			'implements' => ProductPlugin::class,
			'componentClass' => VueComponent::class,
			'view' => 'detail',
			'source' => __DIR__ . '/../templates/product/priceList.js',
			'position' => 55,
			'tab' => 'Prices',
			'params' => ['id'],
		]]);
		$pluginManager->addSetup('?->addComponent(?)', ['@self', [
			'key' => 'productParameter',
			'name' => 'cms-product-parameter',
			'implements' => ProductPlugin::class,
			'componentClass' => VueComponent::class,
			'view' => 'detail',
			'source' => __DIR__ . '/../templates/product/parameter.js',
			'position' => 50,
			'tab' => 'Parameters',
			'params' => ['id'],
		]]);
		$pluginManager->addSetup('?->addComponent(?)', ['@self', [
			'key' => 'productVariants',
			'name' => 'cms-product-variants',
			'implements' => ProductPlugin::class,
			'componentClass' => VueComponent::class,
			'view' => 'detail',
			'source' => __DIR__ . '/../templates/product/variants.js',
			'position' => 40,
			'tab' => 'Variants',
			'params' => ['id'],
		]]);
		$pluginManager->addSetup('?->addComponent(?)', ['@self', [
			'key' => 'productRelated',
			'name' => 'cms-product-related',
			'implements' => ProductPlugin::class,
			'componentClass' => VueComponent::class,
			'view' => 'detail',
			'source' => __DIR__ . '/../templates/product/related.js',
			'position' => 20,
			'tab' => 'Related',
			'params' => ['id'],
		]]);

		$pluginManager->addSetup('?->addComponent(?)', ['@self', [
			'key' => 'productCategoryDefault',
			'name' => 'cms-product-category-default',
			'implements' => ProductCategoryPlugin::class,
			'componentClass' => VueComponent::class,
			'view' => 'default',
			'source' => __DIR__ . '/../templates/category/default.js',
			'position' => 100,
			'tab' => 'Category',
			'params' => [],
		]]);
		$pluginManager->addSetup('?->addComponent(?)', ['@self', [
			'key' => 'productCategoryOverview',
			'name' => 'cms-product-category-overview',
			'implements' => ProductCategoryPlugin::class,
			'componentClass' => VueComponent::class,
			'view' => 'detail',
			'source' => __DIR__ . '/../templates/category/overview.js',
			'position' => 100,
			'tab' => 'Overview',
			'params' => ['id'],
		]]);
		$pluginManager->addSetup('?->addComponent(?)', ['@self', [
			'key' => 'productCategoryProducts',
			'name' => 'cms-product-category-products',
			'implements' => ProductCategoryPlugin::class,
			'componentClass' => VueComponent::class,
			'view' => 'detail',
			'source' => __DIR__ . '/../templates/category/products.js',
			'position' => 80,
			'tab' => 'Products',
			'params' => ['id'],
		]]);
	}
}
