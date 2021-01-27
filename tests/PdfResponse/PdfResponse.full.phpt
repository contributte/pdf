<?php declare(strict_types = 1);

/**
 * Test: Contributte\PdfResponse\PdfResponse.
 */

use Contributte\PdfResponse\PdfResponse;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\UrlScript;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

test(function (): void {
	$origData = file_get_contents(__DIR__ . '/templates/example1.htm');
	$fileResponse = new PdfResponse($origData);
	$fileResponse->setSaveMode(PdfResponse::INLINE);

	ob_start();
	$fileResponse->send(new Request(new UrlScript()), new Response());
	$actualData = ob_get_clean();

	Assert::match('#^%PDF-#i', $actualData);
});
