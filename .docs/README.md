# Contributte\PdfResponse

## Content

- [Usage - how use it](#usage)
    - [How to prepare PDF from template](#how-to-prepare-pdf-from-template)
    - [Save file to server](#save-file-to-server)
    - [Attach file to an email](#attach-file-to-an-email)
    - [Force file to download](#force-file-to-download)
	- [Force file to display in a browser](#force-file-to-display-in-a-browser)
	- [Set a pdf background easily](#set-a-pdf-background-easily)
	- [Create pdf with latte only](#create-pdf-with-latte-only)
	- [Configuration of custom temp dir for mPDF in PdfResponse](#configuration-of-custom-temp-dir-for-mpdf-in-pdfresponse)

## Usage

### How to prepare PDF from template

```php
// in a Presenter
public function actionPdf()
{
    $template = $this->createTemplate();
    $template->setFile(__DIR__ . "/path/to/template.latte");
    $template->someValue = 123;
    // Tip: In template to make a new page use <pagebreak>

    $pdf = new \Contributte\PdfResponse\PdfResponse($template);

    // optional
    $pdf->documentTitle = date("Y-m-d") . " My super title"; // creates filename 2012-06-30-my-super-title.pdf
    $pdf->pageFormat = "A4-L"; // wide format
    $pdf->getMPDF()->setFooter("|© www.mysite.com|"); // footer

    // do something with $pdf
    $this->sendResponse($pdf);
}
```

### Save file to server

```php
public function actionPdf()
{
    $template = $this->createTemplate();
    $template->setFile(__DIR__ . "/path/to/template.latte");

    $pdf = new \Contributte\PdfResponse\PdfResponse($template);

    $pdf->save(__DIR__ . "/path/to/directory"); // as a filename $this->documentTitle will be used
    $pdf->save(__DIR__ . "/path/to/directory", "filename"); // OR use a custom name
}
```

### Attach file to an email

```php
public function actionPdf()
{
    $template = $this->createTemplate();
    $template->setFile(__DIR__ . "/path/to/template.latte");

    $pdf = new \Contributte\PdfResponse\PdfResponse($template);

    $savedFile = $pdf->save(__DIR__ . "/path/to/directory");
    $mail = new Nette\Mail\Message;
    $mail->addTo("john@doe.com");
    $mail->addAttachment($savedFile);
    $mailer = new SendmailMailer();
    $mailer->send($mail);
}
```

### Force file to download

```php
public function actionPdf()
{
    $template = $this->createTemplate();
    $template->setFile(__DIR__ . "/path/to/template.latte");

    $pdf = new \Contributte\PdfResponse\PdfResponse($template);
    $pdf->setSaveMode(PdfResponse::DOWNLOAD); //default behavior
    $this->sendResponse($pdf);
}
```

### Force file to display in a browser

```php
public function actionPdf()
{
    $template = $this->createTemplate();
    $template->setFile(__DIR__ . "/path/to/template.latte");

    $pdf = new \Contributte\PdfResponse\PdfResponse($template);
    $pdf->setSaveMode(PdfResponse::INLINE);
    $this->sendResponse($pdf);
}
```

### Set a pdf background easily

```php
public function actionPdf()
{
    $pdf = new \Contributte\PdfResponse\PdfResponse('');
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

### Create pdf with latte only

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

    $pdf = new \Contributte\PdfResponse\PdfResponse($template);
    $this->sendResponse($pdf);
}
```

### Configuration of custom temp dir for mPDF in PdfResponse

```yml
services:
    -
        factory: Contributte\PdfResponse\PdfResponse
        setup:
            - $mpdfConfig([tempDir: %tempDir%/mpdf])
```


See also
---

- [Nette forum](https://forum.nette.org/cs/3726-addon-pdfresponse-pdfresponse) (czech)
- [Componette](https://componette.com/joseki/pdfresponse/)



-----

Thanks for testing, reporting and contributing.
