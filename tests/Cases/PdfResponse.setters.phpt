<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\PdfResponse\Exceptions\InvalidArgumentException;
use Contributte\PdfResponse\PdfResponse;
use Contributte\Tester\Toolkit;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

Toolkit::test(function (): void {
	$origData = file_get_contents(__DIR__ . '/templates/example1.htm');
	$fileResponse = new PdfResponse($origData);

	// zoom
	$fileResponse->displayZoom = PdfResponse::ZOOM_REAL;
	$fileResponse->displayZoom = 90;
	Assert::exception(
		function () use ($fileResponse): void {
			$fileResponse->displayZoom = 'invalid';
		},
		InvalidArgumentException::class
	);

	// layout
	$fileResponse->displayLayout = PdfResponse::LAYOUT_TWO;
	Assert::exception(
		function () use ($fileResponse): void {
			$fileResponse->displayLayout = 'invalid';
		},
		InvalidArgumentException::class
	);
});
