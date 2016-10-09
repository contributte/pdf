<?php

/**
 * Test: Joseki\Application\Responses\PdfResponse.
 */

use Joseki\Application\Responses\PdfResponse;
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
            'Joseki\Application\Responses\InvalidArgumentException'
        );

        // layout
        $fileResponse->displayLayout = PdfResponse::LAYOUT_TWO;
        Assert::exception(
            function () use ($fileResponse) {
                $fileResponse->displayLayout = "invalid";
            },
            'Joseki\Application\Responses\InvalidArgumentException'
        );
    }
);
