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
use Contributte\PdfResponse\PdfResponse;

// in a Presenter
public function actionPdf()
{
	$template = $this->createTemplate();
	$template->setFile(__DIR__ . "/path/to/template.latte");
	$template->someValue = 123;
	// Tip: In template to make a new page use <pagebreak>

	$pdf = new PdfResponse($template);

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
use Contributte\PdfResponse\PdfResponse;

public function actionPdf()
{
	$template = $this->createTemplate();
	$template->setFile(__DIR__ . "/path/to/template.latte");

	$pdf = new PdfResponse($template);

	$pdf->save(__DIR__ . "/path/to/directory"); // as a filename $this->documentTitle will be used
	$pdf->save(__DIR__ . "/path/to/directory", "filename"); // OR use a custom name
}
```

### Attach file to an email

```php
use Contributte\PdfResponse\PdfResponse;

public function actionPdf()
{
	$template = $this->createTemplate();
	$template->setFile(__DIR__ . "/path/to/template.latte");

	$pdf = new PdfResponse($template);

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
use Contributte\PdfResponse\PdfResponse;

public function actionPdf()
{
	$template = $this->createTemplate();
	$template->setFile(__DIR__ . "/path/to/template.latte");

	$pdf = new PdfResponse($template);
	$pdf->setSaveMode(PdfResponse::DOWNLOAD); //default behavior
	$this->sendResponse($pdf);
}
```

### Force file to display in a browser

```php
use Contributte\PdfResponse\PdfResponse;

public function actionPdf()
{
	$template = $this->createTemplate();
	$template->setFile(__DIR__ . "/path/to/template.latte");

	$pdf = new PdfResponse($template);
	$pdf->setSaveMode(PdfResponse::INLINE);
	$this->sendResponse($pdf);
}
```

### Set a pdf background easily

```php
use Contributte\PdfResponse\PdfResponse;

public function actionPdf()
{
	$pdf = new PdfResponse('');
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
use Contributte\PdfResponse\PdfResponse;

public function actionPdf()
{
	$latte = new Latte\Engine;
	$latte->setTempDirectory('/path/to/cache');
	$latte->addFilter('money', function($val) { return ...; }); // formerly registerHelper()

	$latte->onCompile[] = function($latte) {
		$latte->addMacro(...); // when you want add some own macros, see http://goo.gl/d5A1u2
	};

	$template = $latte->renderToString(__DIR__ . "/path/to/template.latte");

	$pdf = new PdfResponse($template);
	$this->sendResponse($pdf);
}
```

### Configuration of custom temp dir for mPDF in PdfResponse

```neon
services:
	-
		factory: Contributte\PdfResponse\PdfResponse
		setup:
			- $mpdfConfig([tempDir: %tempDir%/mpdf])
```

and in your PHP code:

```php
use Contributte\PdfResponse\PdfResponse;

private PdfResponse $pdfResponse;

public function __construct(PdfResponse $pdfResponse)
{
	$this->pdfResponse = $pdfResponse;
}

public function actionPdf()
{
	$template = $this->createTemplate();
	$template->setFile(__DIR__ . "/path/to/template.latte");

	$this->pdfResponse->setTemplate($template);

	$this->pdfResponse->setSaveMode(PdfResponse::INLINE);
	$this->sendResponse($this->pdfResponse);
}
```
