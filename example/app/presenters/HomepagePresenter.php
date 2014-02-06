<?php

namespace App;

use Joseki\Application\Responses\PdfResponse;

/**
 * Homepage presenter.
 */
class HomepagePresenter extends BasePresenter
{
	public function actionPdf()
	{
		$template = $this->createTemplate()->setFile(__DIR__ . "/../templates/Homepage/default.latte");

		$pdf = new PdfResponse($template);

		$pdf->setSaveMode(PdfResponse::INLINE);

		$this->sendResponse($pdf);
	}
}

