PDF Response for Nette 2.1
===

- sends template as PDF output
- mPDF required - http://www.mpdf1.com/mpdf/download (version 5.*)
- works fine with both Nette 2.0.* and Nette 2.1.*
- no js support
- nice api

Install
---
Recommended installation is via Composer.

    {
        "require":{
            "castamir/pdf-response": ">= 1.2"
        }
    }


Alternative install without Composer:

	libs/mPDF/ (download and place mPDF library here)
	libs/netterobots.txt (prevents robotloader from caching all mPDF classes)
	libs/PdfResponse.php
	
and add the following line to the beggining of libs/PdfResponse.php:

    require __DIR__ . "/mPDF/mpdf.php";


How to prepare PDF from template
---

    // in a Presenter
    public function actionPdf()
    {
        $template = $this->createTemplate()->setFile(APP_DIR . "/templates/myPdf.latte");
        $template->someValue = 123;
        // Tip: In template to make a new page use <pagebreak>

        $pdf = new \PdfResponse($template);

        // optional
        $pdf->documentTitle = date("Y-m-d") . " My super title"; // creates filename 2012-06-30-my-super-title.pdf
        $pdf->pageFormat = "A4-L"; // wide format
        $pdf->getMPDF()->setFooter("|© www.mysite.com|"); // footer
    }

Save file to server
---

    public function actionPdf()
    {
        $template = $this->createTemplate()->setFile(APP_DIR . "/templates/myPdf.latte");

        $pdf = new \PdfResponse($template);

        $pdf->save(WWW_DIR . "/generated/"); // as a filename $this->documentTitle will be used
        $pdf->save(WWW_DIR . "/generated/", "another file 123); // OR use a custom name
    }


Attach file to an email
---

    public function actionPdf()
    {
        $template = $this->createTemplate()->setFile(APP_DIR . "/templates/myPdf.latte");

        $pdf = new \PdfResponse($template);

        $savedFile = $pdf->save(WWW_DIR . "/contracts/");
        $mail = new Nette\Mail\Message;
        $mail->addTo("john@doe.com");
        $mail->addAttachment($savedFile);
        $mailer = new SendmailMailer();
        $mailer->send($mail);
    }
    

Force file to download
---

    public function actionPdf()
    {
        $template = $this->createTemplate()->setFile(APP_DIR . "/templates/myPdf.latte");

        $pdf = new \PdfResponse($template);
        $pdf->setSaveMode(PdfResponse::DOWNLOAD); //default behavior
        $this->sendResponse($pdf);
    }
    

Force file to display in a browser
---

    public function actionPdf()
    {
        $template = $this->createTemplate()->setFile(APP_DIR . "/templates/myPdf.latte");

        $pdf = new \PdfResponse($template);
        $pdf->setSaveMode(PdfResponse::INLINE);
        $this->sendResponse($pdf);
    }
    

Set a pdf background easily
---

    public function actionPdf()
    {
        $pdf = new \PdfResponse('');
        $pdf->setBackgroundTemplate("/path/to/an/existing/file.pdf");

        // to write into an existing document use the following statements
        $mpdf = $pdf->getMPDF();
        $mpdf->WriteFixedPosHTML('hello world', 1, 10, 10, 10);

        // to write to another page
        $mpdf->AddPage();

        // to move to exact page, use
        $mpdf->page = 3; // = move to 3rd page

        $this->sendResponse($pdf);
    }

More info
---

- http://forum.nette.org/cs/9037-zkusenosti-s-pouzitim-pdfresponse-v-nette-2-0b-a-php-5-3
- http://forum.nette.org/cs/3726-addon-pdfresponse-pdfresponse
- http://addons.nette.org/cs/pdfresponse (old version)
- http://addons.nette.org/cs/pdfresponse2 (current version)
