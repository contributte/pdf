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
                - Joseki\Application\Responses\PdfResponseFactory(%mpdfConfig%)
        ";

        $container = createContainer(new Compiler, $config);

        /** @var Joseki\Application\Responses\PdfResponseFactory $instance */
        $factoryInstance = $container->getByType(Joseki\Application\Responses\PdfResponseFactory::class);
        Assert::same($author, $factoryInstance->mpdfConfig['author']);
        Assert::same($mpdfTmpDir, $factoryInstance->mpdfConfig['tempDir']);
    }
);