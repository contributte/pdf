<?php

/**
 * Test: Contributte\PdfResponse\PdfResponse.
 */

use Contributte\PdfResponse\PdfResponse;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

test(
    function () {
        $origData = file_get_contents(__DIR__ . '/templates/example1.htm');
        $fileResponse = new PdfResponse($origData);
        $fileResponse->setSaveMode(PdfResponse::DOWNLOAD);
        $fileResponse->save(TEMP_DIR, "under_scored.pdf");

        Assert::true(file_exists(TEMP_DIR . "/under_scored.pdf"));
    }
);
