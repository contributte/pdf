<?php declare(strict_types = 1);

namespace Contributte\PdfResponse;

class PdfResponseFactory
{

	/** @var array|null */
	public $mpdfConfig;

	public function __construct(?array $mpdfConfig)
	{
		$this->mpdfConfig = $mpdfConfig;
	}

	public function createResponse(): PdfResponse
	{
		$response = new PdfResponse();

		if (is_array($this->mpdfConfig)) {
			$response->mpdfConfig = $this->mpdfConfig;
		}

		return $response;
	}

}
