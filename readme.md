PDF Response for Nette 2.0
===

- sends template as PDF output
- mPDF required!
- works with Nette 2.0 (2.0.1 released on 2012-02-29)
- without js support

Default file locations
---

	libs/PdfResponse/mPDF/
	libs/PdfResponse/netterobots.txt
	libs/PdfResponse/PdfResponse.php


Use
---

	<?php

	$template = $this->createTemplate()->setFile(APP_DIR . "/templates/myPdf.latte");

	$pdf = new PdfResponse($template);
	$pdf->test(); // shortcut for template render in browser: $this->template->render();die;

	$this->sendResponse($pdf);

	?>

More info
---

- http://forum.nette.org/cs/3726-addon-pdfresponse-pdfresponse
- http://addons.nette.org/cs/pdfresponse