PDF Response for Nette 2.0
===

- sends template as PDF output
- mPDF required - http://www.mpdf1.com/mpdf/download (version 5.6 tested)
- works with Nette 2.0.8 (released on 2013-01-01)
- no js support
- nice api

Default file locations
---

	libs/mPDF/
	libs/netterobots.txt (prevents from caching all mPDF classes)
	libs/PdfResponse.php (anywhere)


Use
---

	<?php

	use PdfResponse;

	class MyPresenter extends Nette\Application\UI\Presenter {

        public function actionPdf() {
            $template = $this->createTemplate()->setFile(APP_DIR . "/templates/myPdf.latte");
            $template->someValue = 123;
            // Tip: In template to make a new page use <pagebreak>

            $pdf = new \PdfResponse($template);

            // optional
            $pdf->documentTitle = date("Y-m-d") . " My super title"; // creates filename 2012-06-30-my-super-title.pdf
            $pdf->pageFormat = "A4-L"; // wide format
            $pdf->getMPDF()->setFooter("|© www.mysite.com|"); // footer

            // now you have 2 posibilites:

            // 1. save file to server
            $pdf->save(WWW_DIR . "/generated/"); // as a filename $this->documentTitle will be used
            $pdf->save(WWW_DIR . "/generated/", "another file 123); // OR use a custom name

            // OR in case of mail attachment, returns path to file on server
            $savedFile = $pdf->save(WWW_DIR . "/contracts/");
            $mail = new Nette\Mail\Message;
            $mail->addTo("john@doe.com");
            $mail->addAttachment($savedFile);
            $mail->send();

            // 2. send pdf file to output (save/open by user) and terminate
            $this->sendResponse($pdf);
        }
    }

	?>

More info
---

- http://forum.nette.org/cs/9037-zkusenosti-s-pouzitim-pdfresponse-v-nette-2-0b-a-php-5-3
- http://forum.nette.org/cs/3726-addon-pdfresponse-pdfresponse
- http://addons.nette.org/cs/pdfresponse
