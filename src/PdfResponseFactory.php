<?php declare(strict_types = 1);

namespace Contributte\PdfResponse;

class PdfResponseFactory
{

	/** @var array<mixed> */
	public array $mpdfConfig;

	/** @param array<mixed> $mpdfConfig */
	public function __construct(array $mpdfConfig)
	{
		$this->mpdfConfig = $mpdfConfig;
	}

	public function createResponse(): PdfResponse
	{
		$response = new PdfResponse();

		$response->mpdfConfig = $this->mpdfConfig;

		return $response;
	}

}
