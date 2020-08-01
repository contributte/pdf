<?php

/**
 * Test: Contributte\PdfResponse\PdfResponse.
 */

use Contributte\PdfResponse\PdfResponse;
use Contributte\PdfResponse\InvalidArgumentException;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

test(
    function () {
        $origData = file_get_contents(__DIR__ . '/templates/example1.htm');
        $fileResponse = new PdfResponse($origData);

        // zoom
        $fileResponse->displayZoom = PdfResponse::ZOOM_REAL;
        $fileResponse->displayZoom = 90;
        Assert::exception(
            function () use ($fileResponse) {
                $fileResponse->displayZoom = "invalid";
            },
            InvalidArgumentException::class
        );

        // layout
        $fileResponse->displayLayout = PdfResponse::LAYOUT_TWO;
        Assert::exception(
            function () use ($fileResponse) {
                $fileResponse->displayLayout = "invalid";
            },
            InvalidArgumentException::class
        );
    }
);
