PDF Response for Nette 2
===

[![Build Status](https://travis-ci.org/Joseki/PdfResponse.svg?branch=master)](https://travis-ci.org/Joseki/PdfResponse)
[![Latest Stable Version](https://poser.pugx.org/joseki/pdf-response/v/stable)](https://packagist.org/packages/joseki/pdf-response)
[![Total Downloads](https://poser.pugx.org/joseki/pdf-response/downloads)](https://packagist.org/packages/joseki/pdf-response)

- sends template as PDF output using mPDF library
- works with Nette v2.2+
- requires PHP 5.4 or higher

Install
---
Installation via Composer.

```sh
composer require joseki/pdf-response ">= 2.1"
```


How to prepare PDF from template
---

```php
// in a Presenter
public function actionPdf()
{
    $template = $this->createTemplate();
    $template->setFile(__DIR__ . "/path/to/template.latte");
    $template->someValue = 123;
    // Tip: In template to make a new page use <pagebreak>

    $pdf = new \Joseki\Application\Responses\PdfResponse($template);

    // optional
    $pdf->documentTitle = date("Y-m-d") . " My super title"; // creates filename 2012-06-30-my-super-title.pdf
    $pdf->pageFormat = "A4-L"; // wide format
    $pdf->getMPDF()->setFooter("|© www.mysite.com|"); // footer
    
    // do something with $pdf
    $this->sendResponse($pdf);
}
```

Save file to server
---

```php
public function actionPdf()
{
    $template = $this->createTemplate();
    $template->setFile(__DIR__ . "/path/to/template.latte");

    $pdf = new \Joseki\Application\Responses\PdfResponse($template);

    $pdf->save(__DIR__ . "/path/to/directory"); // as a filename $this->documentTitle will be used
    $pdf->save(__DIR__ . "/path/to/directory", "filename"); // OR use a custom name
}
```

Attach file to an email
---

```php
public function actionPdf()
{
    $template = $this->createTemplate();
    $template->setFile(__DIR__ . "/path/to/template.latte");

    $pdf = new \Joseki\Application\Responses\PdfResponse($template);

    $savedFile = $pdf->save(__DIR__ . "/path/to/directory");
    $mail = new Nette\Mail\Message;
    $mail->addTo("john@doe.com");
    $mail->addAttachment($savedFile);
    $mailer = new SendmailMailer();
    $mailer->send($mail);
}
```

Force file to download
---

```php
public function actionPdf()
{
    $template = $this->createTemplate();
    $template->setFile(__DIR__ . "/path/to/template.latte");

    $pdf = new \Joseki\Application\Responses\PdfResponse($template);
    $pdf->setSaveMode(PdfResponse::DOWNLOAD); //default behavior
    $this->sendResponse($pdf);
}
```

Force file to display in a browser
---

```php
public function actionPdf()
{
    $template = $this->createTemplate();
    $template->setFile(__DIR__ . "/path/to/template.latte");

    $pdf = new \Joseki\Application\Responses\PdfResponse($template);
    $pdf->setSaveMode(PdfResponse::INLINE);
    $this->sendResponse($pdf);
}
```   

Set a pdf background easily
---

```php
public function actionPdf()
{
    $pdf = new Joseki\Application\Responses\PdfResponse('');
    $pdf->setBackgroundTemplate(__DIR__ . "/path/to/an/existing/file.pdf");

    // to write into an existing document use the following statements
    $mpdf = $pdf->getMPDF();
    $mpdf->WriteFixedPosHTML('hello world', 1, 10, 10, 10);

    // to write to another page
    $mpdf->AddPage();

    // to move to exact page, use
    $mpdf->page = 3; // = move to 3rd page

    $this->sendResponse($pdf);
}
```

Create pdf with latte only
---

```php
public function actionPdf()
{
    $latte = new Latte\Engine;
    $latte->setTempDirectory('/path/to/cache');
    $latte->addFilter('money', function($val) { return ...; }); // formerly registerHelper()

    $latte->onCompile[] = function($latte) {
        $latte->addMacro(...); // when you want add some own macros, see http://goo.gl/d5A1u2
    };

    $template = $latte->renderToString(__DIR__ . "/path/to/template.latte");

    $pdf = new \Joseki\Application\Responses\PdfResponse($template);
    $this->sendResponse($pdf);
}
```

See also
---

- [Nette forum](http://forum.nette.org/cs/3726-addon-pdfresponse-pdfresponse) (czech)
- [Nette Addons](http://addons.nette.org/joseki/pdf-response)
