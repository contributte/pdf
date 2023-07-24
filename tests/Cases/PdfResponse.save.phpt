<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\PdfResponse\PdfResponse;
use Contributte\Tester\Environment;
use Contributte\Tester\Toolkit;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

Toolkit::test(function (): void {
	$origData = file_get_contents(__DIR__ . '/templates/example1.htm');
	$fileResponse = new PdfResponse($origData);
	$fileResponse->setSaveMode(PdfResponse::DOWNLOAD);
	$fileResponse->save(Environment::getTestDir(), 'under_scored.pdf');

	Assert::true(file_exists(Environment::getTestDir() . '/under_scored.pdf'));
});
