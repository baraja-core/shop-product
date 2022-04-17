<?php

declare(strict_types=1);

namespace Baraja\Shop\Product;


final class Validators
{
	public static function validateEAN13(string $barcode): bool
	{
		if (preg_match('/^\d{13}$/', $barcode) !== 1) { // check to see if barcode is 13 digits long
			return false;
		}
		$digit = static fn(int $position): int => (int) $barcode[$position];

		// 1. Add the values of the digits in the even-numbered positions: 2, 4, 6, etc.
		$evenSum = $digit(1) + $digit(3) + $digit(5) + $digit(7) + $digit(9) + $digit(11);

		// 2. Multiply this result by 3.
		$evenSumThree = $evenSum * 3;

		// 3. Add the values of the digits in the odd-numbered positions: 1, 3, 5, etc.
		$oddSum = $digit(0) + $digit(2) + $digit(4) + $digit(6) + $digit(8) + $digit(10);

		// 4. Sum the results of steps 2 and 3.
		$totalSum = $evenSumThree + $oddSum;

		// 5. The check character is the smallest number which, when added to the result in step 4, produces a multiple of 10.
		$nextTen = (ceil($totalSum / 10)) * 10;
		$checkDigit = $nextTen - $totalSum;

		// if the check digit and the last digit of the barcode are OK return true;
		return ((string) $checkDigit) === $barcode[12];
	}


	public static function isValidIsbn10(string $isbn): bool
	{
		$isbn = str_replace('-', '', $isbn);
		$check = 0;
		for ($i = 0; $i < 10; $i++) {
			if (strtolower($isbn[$i]) === 'x') {
				$check += 10 * (10 - $i);
			} elseif (is_numeric($isbn[$i])) {
				$check += (int) $isbn[$i] * (10 - $i);
			} else {
				return false;
			}
		}

		return $check % 11 === 0;
	}


	public static function isValidIsbn13(string $isbn): bool
	{
		$isbn = str_replace('-', '', $isbn);
		$check = 0;
		for ($i = 0; $i < 13; $i += 2) {
			$check += (int) $isbn[$i];
		}
		for ($i = 1; $i < 12; $i += 2) {
			$check += 3 * (int) $isbn[$i];
		}

		return $check % 10 === 0;
	}
}
