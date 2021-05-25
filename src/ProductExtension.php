<?php

declare(strict_types=1);

namespace Baraja\Shop\Product;


use Baraja\Doctrine\ORM\DI\OrmAnnotationsExtension;
use Baraja\Plugin\Component\VueComponent;
use Baraja\Plugin\PluginComponentExtension;
use Baraja\Plugin\PluginManager;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;

final class ProductExtension extends CompilerExtension
{
	/**
	 * @return string[]
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

		/** @var ServiceDefinition $pluginManager */
		$pluginManager = $this->getContainerBuilder()->getDefinitionByType(PluginManager::class);
		$pluginManager->addSetup('?->addComponent(?)', ['@self', [
			'key' => 'productDefault',
			'name' => 'cms-product-default',
			'implements' => ProductPlugin::class,
			'componentClass' => VueComponent::class,
			'view' => 'default',
			'source' => __DIR__ . '/../templates/default.js',
			'position' => 100,
			'tab' => 'Products',
			'params' => [],
		]]);
	}
}
