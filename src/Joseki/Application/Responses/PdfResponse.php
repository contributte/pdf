<?php

namespace Joseki\Application\Responses;

use Nette\Bridges\ApplicationLatte\Template;
use Nette\FileNotFoundException;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Templating\ITemplate;
use Nette\Utils\Strings;
use Nette;
use mPDF;



/**
 * PdfResponse
 * -----------
 * Wrapper of mPDF.
 * Simple PDF generator for Nette Framework
 *
 * @author     Jan Kuchař
 * @author     Tomáš Votruba
 * @author     Miroslav Paulík
 * @copyright  Copyright (c) 2010 Jan Kuchař (http://mujserver.net)
 * @license    LGPL
 * @link       http://addons.nette.org/cs/pdfresponse2
 */
class PdfResponse extends Nette\Object implements Nette\Application\IResponse
{
	/** possible save modes */
	const INLINE = "I";

	const DOWNLOAD = "D";

	/** @var string save mode */
	public $saveMode = self::DOWNLOAD;

	/** @var  string|ITemplate|Template */
	private $source;

	/** @var string path to (PDF) file */
	private $backgroundTemplate;

	/**  Portrait page orientation */
	const ORIENTATION_PORTRAIT = "P";

	/** Landscape page orientation */
	const ORIENTATION_LANDSCAPE = "L";

	/** @var string ORIENTATION_PORTRAIT or ORIENTATION_LANDSCAPE */
	public $pageOrientation = self::ORIENTATION_PORTRAIT;

	/** see http://mpdf1.com/manual/index.php?tid=184 */
	public $pageFormat = "A4";

	/**
	 * Margins in this order:
	 * top, right, bottom, left, header, footer
	 *
	 * Please use values higher than 0. In some PDF browser zero values may cause problems!
	 */
	public $pageMargins = "16,15,16,15,9,9";

	public $documentAuthor = "Nette Framework - Pdf response";

	public $documentTitle = "New document";

	/**
	 * Specify the initial Display Mode when the PDF file is opened in Adobe Reader
	 * see http://mpdf1.com/manual/index.php?tid=128&searchstring=SetDisplayMode
	 */
	public $displayZoom = "default";

	public $displayLayout = "continuous";

	/** @var array onBeforeComplete event */
	public $onBeforeComplete = array();

	/** @var bool */
	public $multiLanguage = FALSE;

	/** Additional stylesheet as a html string */
	public $styles = "";

	/**
	 * Ignore styles in HTML document
	 * When using this feature, you MUST also install SimpleHTMLDom to your application!
	 *
	 * @var bool
	 */
	public $ignoreStylesInHTMLDocument = FALSE;

	/** @var mPDF */
	private $mPDF = NULL;

	/** @var  mPDF */
	private $generatedFile;



	/**
	 * @param Template|ITemplate|string $source
	 * @throws InvalidArgumentException
	 * @internal param
	 */
	public function __construct($source)
	{
		if (is_object($source)) {
			if (!($source instanceof ITemplate || $source instanceof Template)) {
				$class = get_class($source);
				throw new InvalidArgumentException("Unsupported template class '$class'.");
			}
		} else if (!is_string($source)) {
			$type = gettype($source);
			throw new InvalidArgumentException("Invalid source type. Expected (html) string of instance of Nette\Templating\ITemplate, Nette\Bridges\ApplicationLatte\Template or Latte\Template, but '$type' given.");
		}
		$this->source = $source;
	}



	/**
	 * @param string $pathToBackgroundTemplate
	 * @throws FileNotFoundException
	 */
	public function setBackgroundTemplate($pathToBackgroundTemplate)
	{
		if (!file_exists($pathToBackgroundTemplate)) {
			throw new FileNotFoundException("File '$pathToBackgroundTemplate' not found.");
		}
		$this->backgroundTemplate = $pathToBackgroundTemplate;

		// if background exists, then add it as a background
		$mpdf = $this->getMPDF();
		$mpdf->SetImportUse();
		$pagecount = $mpdf->SetSourceFile($this->backgroundTemplate);
		for ($i = 1; $i <= $pagecount; $i++) {
			$tplId = $mpdf->ImportPage($i);
			$mpdf->UseTemplate($tplId);

			if ($i < $pagecount) {
				$mpdf->AddPage();
			}
		}
		$mpdf->page = 1;
	}



	/**
	 * Getts margins as array
	 *
	 * @throws InvalidStateException
	 * @throws InvalidArgumentException
	 * @return array
	 */
	function getMargins()
	{
		$margins = explode(",", $this->pageMargins);
		if (count($margins) !== 6) {
			throw new InvalidStateException("You must specify all margins! For example: 16,15,16,15,9,9");
		}

		$dictionary = array(
			0 => "top",
			1 => "right",
			2 => "bottom",
			3 => "left",
			4 => "header",
			5 => "footer"
		);

		$marginsOut = array();
		foreach ($margins AS $key => $val) {
			$val = (int)$val;
			if ($val < 0) {
				throw new InvalidArgumentException("Margin must not be negative number!");
			}
			$marginsOut[$dictionary[$key]] = $val;
		}

		return $marginsOut;
	}



	/**
	 * Sends response to output
	 *
	 * @param IRequest $httpRequest
	 * @param IResponse $httpResponse
	 * @return void
	 */
	public function send(IRequest $httpRequest, IResponse $httpResponse)
	{
		$mpdf = $this->build();
		$mpdf->Output(Strings::webalize($this->documentTitle) . ".pdf", $this->saveMode);
	}



	/**
	 * Builds final pdf
	 *
	 * @return mPDF
	 * @throws \Exception
	 */
	private function build()
	{
		if (empty($this->documentTitle)) {
			throw new \Exception ("Var 'documentTitle' cannot be empty.");
		}
		if ($this->ignoreStylesInHTMLDocument && !class_exists('simple_html_dom')) {
			throw new MissingServiceException("Class 'simple_html_dom' not found. SimpleHTMLDom is propably missing.");
		}

		if ($this->generatedFile) { // singleton
			return $this->generatedFile;
		}

		if ($this->source instanceof ITemplate || $this->source instanceof Template) {
			$html = $this->source->__toString();
		} else {
			$html = $this->source;
		}

		// Fix: $html can't be empty (mPDF generates Fatal error)
		if (empty($html)) {
			$html = '<html><body></body></html>';
		}

		$mpdf = $this->getMPDF();
		$mpdf->biDirectional = $this->multiLanguage;
		$mpdf->SetAuthor($this->documentAuthor);
		$mpdf->SetTitle($this->documentTitle);
		$mpdf->SetDisplayMode($this->displayZoom, $this->displayLayout);
		$mpdf->showImageErrors = TRUE;

		// copied from mPDF -> removes comments
		$html = preg_replace('/<!--mpdf/i', '', $html);
		$html = preg_replace('/mpdf-->/i', '', $html);
		$html = preg_replace('/<\!\-\-.*?\-\->/s', '', $html);

		// @see: http://mpdf1.com/manual/index.php?tid=121&searchstring=writeHTML
		if ($this->ignoreStylesInHTMLDocument) {
			// deletes all <style> tags
			$parsedHtml = new simple_html_dom($html);
			foreach ($parsedHtml->find("style") AS $el) {
				$el->outertext = "";
			}
			$html = $parsedHtml->__toString();

			$mode = 2; // If <body> tags are found, all html outside these tags are discarded, and the rest is parsed as content for the document. If no <body> tags are found, all html is parsed as content. Prior to mPDF 4.2 the default CSS was not parsed when using mode #2
		} else {
			$mode = 0; // Parse all: HTML + CSS
		}

		// Add content
		$mpdf->WriteHTML($html, $mode);

		// Add styles
		if (!empty($this->styles)) {
			$mpdf->WriteHTML($this->styles, 1);
		}

		$mpdf->page = count($mpdf->pages); //set pointer to last page to force render of all pages
		$this->onBeforeComplete[] = $mpdf;
		$this->generatedFile = $mpdf;

		return $this->generatedFile;
	}



	/**
	 * Returns mPDF object
	 *
	 * @throws InvalidStateException
	 * @return mPDF
	 */
	public function getMPDF()
	{
		if (!$this->mPDF instanceof mPDF) {
			$mpdf = $this->createMPDF();
			if (!($mpdf instanceof mPDF)) {
				throw new InvalidStateException("Callback function createMPDF must return mPDF object!");
			}
			$this->mPDF = $mpdf;
		}

		return $this->mPDF;
	}



	/**
	 * Creates and returns mPDF object
	 *
	 * @return mPDF
	 */
	public function createMPDF()
	{
		$margins = $this->getMargins();

		$mpdf = new mPDF('utf-8', // string $codepage
			$this->pageFormat, // mixed $format
			'', // float $default_font_size
			'', // string $default_font
			$margins["left"], // float $margin_left
			$margins["right"], // float $margin_right
			$margins["top"], // float $margin_top
			$margins["bottom"], // float $margin_bottom
			$margins["header"], // float $margin_header
			$margins["footer"], // float $margin_footer
			$this->pageOrientation);

		return $mpdf;
	}



	/**
	 * Save file to target location
	 * Note: $name overrides property $documentTitle
	 *
	 * @param string $dir path to directory
	 * @param string $filename
	 * @return string
	 */
	public function save($dir, $filename = NULL)
	{
		$pdf = $this->build();
		$file = $pdf->output($filename, "S");
		$filename = Strings::webalize($filename ? : $this->documentTitle) . ".pdf";

		file_put_contents($dir . $filename, $file);

		return $dir . $filename;
	}



	/**
	 * Return generated PDF as a string
	 *
	 * @return string
	 */
	public function __toString()
	{
		$pdf = $this->build();
		return $pdf->Output("", "S");
	}



	/**
	 * To force download, use PdfResponse::DOWNLOAD
	 * To show pdf in browser, use PdfResponse::INLINE
	 *
	 * @param string $saveMode
	 * @throws InvalidArgumentException
	 */
	public function setSaveMode($saveMode)
	{
		if (!in_array($saveMode, array(self::DOWNLOAD, self::INLINE))) {
			throw new InvalidArgumentException("Invalid mode");
		}
		$this->saveMode = $saveMode;
	}

}




class MissingServiceException extends \LogicException
{

}




class InvalidStateException extends \LogicException
{

}




class InvalidArgumentException extends \LogicException
{

}
