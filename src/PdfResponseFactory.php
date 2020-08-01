<?php

declare(strict_types=1);

namespace Contributte\PdfResponse;

/**
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
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

        $response->mpdfConfig = $this->mpdfConfig;

        return $response;
    }
}
