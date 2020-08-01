<?php

/**
 * Test: Contributte\PdfResponse\PdfResponse and Symfony crawler
 */

use Contributte\PdfResponse\PdfResponse;
use Nette\Http;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

test(
    function () {
        $origData = file_get_contents(__DIR__ . '/templates/example2.htm');
        $fileResponse = new PdfResponse($origData);
        $fileResponse->setSaveMode(PdfResponse::INLINE);
        $fileResponse->ignoreStylesInHTMLDocument = true;

        ob_start();
        $fileResponse->send(new Http\Request(new Http\UrlScript), new Http\Response);
        $actualData = ob_get_clean();

        Assert::match('#^%PDF-#i', $actualData);
    }
);
