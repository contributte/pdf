PDF Response for Nette 2.0
===

- sends template as PDF output
- mPDF required - http://www.mpdf1.com/mpdf/download (version 5.6 tested)
- works with Nette 2.0.8 (released on 2013-01-01)
- no js support
- nice api

Install
---
Recommended installation is via Composer.

    {
        "require":{
            "castamir/pdf-response": "@dev"
        }
    }


Alternative install without Composer:

	libs/mPDF/ (download and place mPDF library here)
	libs/netterobots.txt (prevents robotloader from caching all mPDF classes)
	libs/PdfResponse.php
	
and add the following line to the beggining of libs/PdfResponse.php:

    require __DIR__ . "/mPDF/mpdf.php";


How to create PDF from template
---

	use PdfResponse;

	class MyPresenter extends Nette\Application\UI\Presenter {

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
            $mail->send();
        }
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
    }
    

Force file display in browser
---

    public function actionPdf()
        {
            $template = $this->createTemplate()->setFile(APP_DIR . "/templates/myPdf.latte");
            
            $pdf = new \PdfResponse($template);
            
            $pdf->setSaveMode(PdfResponse::INLINE);
            
            $this->sendResponse($pdf);
        }
    }
    

NEW: Set a pdf background easily
---

    public function actionPdf()
        {
            $template = $this->createTemplate()->setFile(APP_DIR . "/templates/myPdf.latte");
            
            $pdf = new \PdfResponse($template);

            $pdf->setBackgroundTemplate(APP_DIR . "/templates/PDF_template.pdf");

            $this->sendResponse($pdf);
        }
    }

More info
---

- http://forum.nette.org/cs/9037-zkusenosti-s-pouzitim-pdfresponse-v-nette-2-0b-a-php-5-3
- http://forum.nette.org/cs/3726-addon-pdfresponse-pdfresponse
- http://addons.nette.org/cs/pdfresponse
