<?php declare(strict_types = 1);

use Contributte\PdfResponse\PdfResponseFactory;
use Nette\DI\Compiler;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

test(
	function () {
		$author = 'Jan Novák';
		$mpdfTmpDir = 'tmp/mpdf';

		$config = sprintf('
            parameters:
                mpdfConfig:
                    tempDir: %s
                    author: %s', $mpdfTmpDir, $author);

		$config .= '
            services:
                - Contributte\PdfResponse\PdfResponseFactory(%mpdfConfig%)
        ';

		$container = createContainer(new Compiler(), $config);

		/** @var PdfResponseFactory $factoryInstance */
		$factoryInstance = $container->getByType(PdfResponseFactory::class);
		Assert::same($author, $factoryInstance->mpdfConfig['author']);
		Assert::same($mpdfTmpDir, $factoryInstance->mpdfConfig['tempDir']);
	}
);
