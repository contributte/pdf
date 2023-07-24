<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\PdfResponse\PdfResponseFactory;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addConfig(Neonkit::load(<<<'NEON'
			parameters:
				mpdfConfig:
					tempDir: tmp/mpdf
					author: test
			services:
				- Contributte\PdfResponse\PdfResponseFactory(%mpdfConfig%)
			NEON
			));
		})
		->build();

	/** @var PdfResponseFactory $factoryInstance */
	$factoryInstance = $container->getByType(PdfResponseFactory::class);
	Assert::same('test', $factoryInstance->mpdfConfig['author']);
	Assert::same('tmp/mpdf', $factoryInstance->mpdfConfig['tempDir']);
});
