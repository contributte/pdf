<?php

use Nette\DI\Compiler;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

test(
    function () {
        $author = 'Jan Novák';
        $mpdfTmpDir = 'tmp/mpdf';

        $config = "
            parameters:
                mpdfConfig:
                    tempDir: $mpdfTmpDir
                    author: $author

            services:
                - Contributte\PdfResponse\PdfResponseFactory(%mpdfConfig%)
        ";

        $container = createContainer(new Compiler, $config);

        /** @var Contributte\PdfResponse\PdfResponseFactory $instance */
        $factoryInstance = $container->getByType(Contributte\PdfResponse\PdfResponseFactory::class);
        Assert::same($author, $factoryInstance->mpdfConfig['author']);
        Assert::same($mpdfTmpDir, $factoryInstance->mpdfConfig['tempDir']);
    }
);