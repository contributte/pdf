<?php

declare(strict_types=1);

namespace Joseki\Application\Responses;

if (false) {
	/** @deprecated use Contributte\PdfResponse\InvalidArgumentException */
	class InvalidArgumentException
	{
	}
	
	/** @deprecated use Contributte\PdfResponse\InvalidStateException */
	class InvalidStateException
	{
	}
	
	/** @deprecated use Contributte\PdfResponse\MissingServiceException */
	class MissingServiceException
	{
	}
	
	/** @deprecated use Contributte\PdfResponse\PdfResponse */
	class PdfResponse
	{
	}
	/** @deprecated use Contributte\PdfResponse\PdfResponseFactory */
	class PdfResponseFactory
	{
	}
} elseif (!class_exists(InvalidArgumentException::class)) {
	class_alias(\Contributte\PdfResponse\InvalidArgumentException::class, InvalidArgumentException::class);
	class_alias(\Contributte\PdfResponse\InvalidStateException::class, InvalidStateException::class);
	class_alias(\Contributte\PdfResponse\MissingServiceException::class, MissingServiceException::class);
	class_alias(\Contributte\PdfResponse\PdfResponse::class, PdfResponse::class);
	class_alias(\Contributte\PdfResponse\PdfResponseFactory::class, PdfResponseFactory::class);
}
