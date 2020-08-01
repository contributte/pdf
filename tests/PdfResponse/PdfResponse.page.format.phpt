<?php

/**
 * Test: Contributte\PdfResponse\PdfResponse and page format.
 * @httpCode -
 */

use Contributte\PdfResponse\PdfResponse;
use Contributte\PdfResponse\InvalidStateException;
use Nette\Http;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
$origData = file_get_contents(__DIR__ . '/templates/example1.htm');

test(
    function () use ($origData) {
        $fileResponse = new PdfResponse($origData);
        $fileResponse->setSaveMode(PdfResponse::INLINE);
        $fileResponse->pageOrientation = PdfResponse::ORIENTATION_LANDSCAPE;
        $fileResponse->pageFormat = 'A4-L';
        $fileResponse->pageMargins = $fileResponse->getPageMargins();

        ob_start();
        $fileResponse->send(new Http\Request(new Http\UrlScript), new Http\Response);
        $actualData = ob_get_clean();

        Assert::match('#^%PDF-#i', $actualData);
    }
);

test(
    function () use ($origData) {
        $fileResponse = new PdfResponse($origData);
        $fileResponse->getMPDF();

        Assert::exception(
            function () use ($fileResponse) {
                $fileResponse->pageOrientation = PdfResponse::ORIENTATION_LANDSCAPE;
            },
            InvalidStateException::class,
            'mPDF instance already created. Set page orientation before calling getMPDF'
        );
    }
);

test(
    function () use ($origData) {
        $fileResponse = new PdfResponse($origData);
        $fileResponse->getMPDF();

        Assert::exception(
            function () use ($fileResponse) {
                $fileResponse->pageFormat = 'A4-L';
            },
            InvalidStateException::class,
            'mPDF instance already created. Set page format before calling getMPDF'
        );
    }
);

test(
    function () use ($origData) {
        $fileResponse = new PdfResponse($origData);
        $fileResponse->getMPDF();

        Assert::exception(
            function () use ($fileResponse) {
                $fileResponse->pageMargins = $fileResponse->getPageMargins();
            },
            InvalidStateException::class,
            'mPDF instance already created. Set page margins before calling getMPDF'
        );
    }
);
