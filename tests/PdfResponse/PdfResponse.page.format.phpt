<?php

/**
 * Test: Joseki\Application\Responses\PdfResponse and page format.
 * @httpCode -
 */

use Joseki\Application\Responses\PdfResponse;
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
        $actualData = preg_replace('#/(CreationDate|ModDate|ID) .*#', '', $actualData);

        $expectedData = file_get_contents(__DIR__ . '/expected/page.format.pdf');
        $expectedData = preg_replace('#/(CreationDate|ModDate|ID) .*#', '', $expectedData);

        Assert::same($expectedData, $actualData);
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
            'Joseki\Application\Responses\InvalidStateException',
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
            'Joseki\Application\Responses\InvalidStateException',
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
            'Joseki\Application\Responses\InvalidStateException',
            'mPDF instance already created. Set page margins before calling getMPDF'
        );
    }
);
