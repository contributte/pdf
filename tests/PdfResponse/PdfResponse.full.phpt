<?php

/**
 * Test: Contributte\PdfResponse\PdfResponse.
 */

use Contributte\PdfResponse\PdfResponse;
use Nette\Http;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

test(
    function () {
        $origData = file_get_contents(__DIR__ . '/templates/example1.htm');
        $fileResponse = new PdfResponse($origData);
        $fileResponse->setSaveMode(PdfResponse::INLINE);

        ob_start();
        $fileResponse->send(new Http\Request(new Http\UrlScript), new Http\Response);
        $actualData = ob_get_clean();

        Assert::match('#^%PDF-#i', $actualData);
    }
);
