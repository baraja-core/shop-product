<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'shop__product_parameter_color')]
class ProductParameterColor
{
	#[ORM\Id]
	#[ORM\Column(type: 'integer', unique: true, options: ['unsigned' => true])]
	#[ORM\GeneratedValue]
	protected int $id;

	#[ORM\Column(type: 'string', length: 32, unique: true)]
	private string $color;

	#[ORM\Column(type: 'string', length: 7)]
	private string $value;

	#[ORM\Column(type: 'string', length: 32, nullable: true)]
	private ?string $imgPath = null;


	public function __construct(string $color, string $value)
	{
		$this->setColor($color);
		$this->setValue($value);
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getColor(): string
	{
		return $this->color;
	}


	public function setColor(string $color): void
	{
		$color = (string) mb_strtolower(trim($color), 'UTF-8');
		if ($color === '') {
			throw new \InvalidArgumentException('The color name can not be empty.');
		}
		$this->color = $color;
	}


	public function getValue(): string
	{
		return $this->value;
	}


	public function setValue(string $value): void
	{
		$value = mb_strtolower(trim($value), 'UTF-8');
		if ($value === '') {
			throw new \InvalidArgumentException('The colour code can not be empty.');
		}
		if (preg_match('/^#((?:[0-9a-f]{3})|(?:[0-9a-f]{6}))$/', $value, $parser) === 1) {
			assert(isset($parser[1]));
			$this->value = sprintf('#%s', $parser[1]);

			return;
		}
		throw new \InvalidArgumentException(sprintf('The "%s" color has invalid format. Only #000 and #ffffff formats are supported.', $value));
	}


	public function getImgPath(): ?string
	{
		return $this->imgPath;
	}


	public function setImgPath(?string $imgPath): void
	{
		$this->imgPath = $imgPath;
	}
}
