<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Baraja\EcommerceStandard\DTO\ManufacturerInterface;
use Doctrine\ORM\Mapping as ORM;
use Nette\Utils\Strings;

#[ORM\Entity]
#[ORM\Table(name: 'shop__product_manufacturer')]
class ProductManufacturer implements ManufacturerInterface
{
	#[ORM\Id]
	#[ORM\Column(type: 'integer', unique: true, options: ['unsigned' => true])]
	#[ORM\GeneratedValue]
	protected int $id;

	#[ORM\Column(type: 'string', length: 64, nullable: true)]
	private string $name;


	public function __construct(string $name)
	{
		$this->setName($name);
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getName(): string
	{
		return $this->name;
	}


	public function setName(string $name): void
	{
		$this->name = Strings::firstUpper($name);
	}
}
