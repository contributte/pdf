<?php declare(strict_types = 1);

namespace Contributte\PdfResponse;

use Contributte\PdfResponse\Exceptions\InvalidArgumentException;
use Contributte\PdfResponse\Exceptions\InvalidStateException;
use Contributte\PdfResponse\Exceptions\MissingServiceException;
use DOMElement;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;
use Nette\Application\Response;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\FileNotFoundException;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\SmartObject;
use Nette\Utils\Strings;
use setasign\Fpdi\PdfParser\PdfParserException as PdfParserExceptionAlias;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

/**
 * PdfResponse
 * -----------
 * Wrapper of mPDF.
 * Simple PDF generator for Nette Framework
 *
 * @link       http://addons.nette.org/cs/pdfresponse2
 * @property string       $saveMode
 * @property string       $pageOrientation
 * @property string|array $pageFormat
 * @property string       $pageMargins
 * @property string       $documentAuthor
 * @property string       $documentTitle
 * @property string|int   $displayZoom
 * @property string       $displayLayout
 * @property bool         $multiLanguage
 * @property bool         $ignoreStylesInHTMLDocument
 * @method onBeforeComplete($mpdf) @internal
 */
class PdfResponse implements Response
{

	use SmartObject;

	/**
	 * possible save modes
	 */
	public const INLINE = 'I';

	public const DOWNLOAD = 'D';

	/**
	 * Portrait page orientation
	 */
	public const ORIENTATION_PORTRAIT = 'P';

	/**
	 * Landscape page orientation
	 */
	public const ORIENTATION_LANDSCAPE = 'L';

	/** @see https://mpdf.github.io/reference/mpdf-functions/setdisplaymode.html */
	public const ZOOM_DEFAULT = 'default'; // User’s default setting in Adobe Reader

	public const ZOOM_FULLPAGE = 'fullpage'; // Fit a whole page in the screen

	public const ZOOM_FULLWIDTH = 'fullwidth'; // Fit the width of the page in the screen

	public const ZOOM_REAL = 'real'; // Display at real size

	/** @see https://mpdf.github.io/reference/mpdf-functions/setdisplaymode.html */
	public const LAYOUT_SINGLE = 'single'; // Display one page at a time

	public const LAYOUT_CONTINUOUS = 'continuous'; // Display the pages in one column

	public const LAYOUT_TWO = 'two'; // Display the pages in two columns (first page determined by document direction (e.g. RTL))

	public const LAYOUT_TWOLEFT = 'twoleft'; // Display the pages in two columns, with the first page displayed on the left side (mPDF >= 5.2)

	public const LAYOUT_TWORIGHT = 'tworight'; // Display the pages in two columns, with the first page displayed on the right side (mPDF >= 5.2)

	public const LAYOUT_DEFAULT = 'default'; // User’s default setting in Adobe Reader

	/** @var array<mixed> */
	public array $mpdfConfig = [];

	/** @var array<mixed> onBeforeComplete event */
	public array $onBeforeComplete = [];

	/** @var string Additional stylesheet as a html string */
	public string $styles = '';

	private string $documentAuthor = 'Nette Framework - Pdf response';

	private string $documentTitle = 'New document';

	private string|int $displayZoom = self::ZOOM_DEFAULT;

	private string $displayLayout = self::LAYOUT_DEFAULT;

	private bool $multiLanguage = false;

	/** @var bool, REQUIRES symfony/dom-crawler package */
	private bool $ignoreStylesInHTMLDocument = false;

	private string|Template $source;

	/** @var string save mode */
	private string $saveMode = self::DOWNLOAD;

	/** @var string path to (PDF) file */
	private string $backgroundTemplate;

	/** @var string ORIENTATION_PORTRAIT or ORIENTATION_LANDSCAPE */
	private string $pageOrientation = self::ORIENTATION_PORTRAIT;

	/** @var string|array<mixed> see second parameter ($format) at https://mpdf.github.io/reference/mpdf-functions/mpdf.html */
	private string|array $pageFormat = 'A4';

	/** @var string margins: top, right, bottom, left, header, footer */
	private string $pageMargins = '16,15,16,15,9,9';

	private ?Mpdf $mPDF = null;

	private ?Mpdf $generatedFile = null;

	/**
	 * @param Template|string $source
	 * @throws InvalidArgumentException
	 */
	public function __construct(Template|string|null $source = null)
	{
		if ($source === null) {
			return;
		}

		$this->setTemplate($source);
	}

	public function setTemplate(Template|string $source): self
	{
		$this->source = $source;

		return $this;
	}

	public function getDocumentAuthor(): string
	{
		return $this->documentAuthor;
	}

	public function setDocumentAuthor(string $documentAuthor): void
	{
		$this->documentAuthor = $documentAuthor;
	}

	public function getDocumentTitle(): string
	{
		return $this->documentTitle;
	}

	public function setDocumentTitle(string $documentTitle): void
	{
		$this->documentTitle = $documentTitle;
	}

	public function getDisplayZoom(): string|int
	{
		return $this->displayZoom;
	}

	public function setDisplayZoom(string|int $displayZoom): void
	{
		if ((!is_int($displayZoom) || $displayZoom <= 0) && !in_array($displayZoom, [
				self::ZOOM_DEFAULT,
				self::ZOOM_FULLPAGE,
				self::ZOOM_FULLWIDTH,
				self::ZOOM_REAL,
			], true)) {
			throw new InvalidArgumentException("Invalid zoom '" . $displayZoom . "', use PdfResponse::ZOOM_* constants or o positive integer.");
		}

		$this->displayZoom = $displayZoom;
	}

	public function getDisplayLayout(): string
	{
		return $this->displayLayout;
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function setDisplayLayout(string $displayLayout): void
	{
		if (!in_array($displayLayout, [self::LAYOUT_DEFAULT, self::LAYOUT_CONTINUOUS, self::LAYOUT_SINGLE, self::LAYOUT_TWO, self::LAYOUT_TWOLEFT, self::LAYOUT_TWORIGHT], true)) {
			throw new InvalidArgumentException("Invalid layout '" . $displayLayout . "', use PdfResponse::LAYOUT* constants.");
		}

		$this->displayLayout = $displayLayout;
	}

	public function isMultiLanguage(): bool
	{
		return $this->multiLanguage;
	}

	public function setMultiLanguage(bool $multiLanguage): void
	{
		$this->multiLanguage = $multiLanguage;
	}

	public function isIgnoreStylesInHTMLDocument(): bool
	{
		return $this->ignoreStylesInHTMLDocument;
	}

	public function setIgnoreStylesInHTMLDocument(bool $ignoreStylesInHTMLDocument): void
	{
		$this->ignoreStylesInHTMLDocument = $ignoreStylesInHTMLDocument;
	}

	public function getSaveMode(): string
	{
		return $this->saveMode;
	}

	/**
	 * To force download, use PdfResponse::DOWNLOAD
	 * To show pdf in browser, use PdfResponse::INLINE
	 *
	 * @throws InvalidArgumentException
	 */
	public function setSaveMode(string $saveMode): void
	{
		if (!in_array($saveMode, [self::DOWNLOAD, self::INLINE], true)) {
			throw new InvalidArgumentException("Invalid mode '" . $saveMode . "', use PdfResponse::INLINE or PdfResponse::DOWNLOAD instead.");
		}

		$this->saveMode = $saveMode;
	}

	public function getPageOrientation(): string
	{
		return $this->pageOrientation;
	}

	/**
	 * @throws InvalidStateException
	 * @throws InvalidArgumentException
	 */
	public function setPageOrientation(string $pageOrientation): void
	{
		if ($this->mPDF !== null) {
			throw new InvalidStateException('mPDF instance already created. Set page orientation before calling getMPDF');
		}

		if (!in_array($pageOrientation, [self::ORIENTATION_PORTRAIT, self::ORIENTATION_LANDSCAPE], true)) {
			throw new InvalidArgumentException('Unknown page orientation');
		}

		$this->pageOrientation = $pageOrientation;
	}

	/**
	 * @return string|array<mixed>
	 */
	public function getPageFormat(): string|array
	{
		return $this->pageFormat;
	}

	/**
	 * @param string|array<mixed> $pageFormat
	 * @throws InvalidStateException
	 */
	public function setPageFormat(string|array $pageFormat): void
	{
		if ($this->mPDF !== null) {
			throw new InvalidStateException('mPDF instance already created. Set page format before calling getMPDF');
		}

		$this->pageFormat = $pageFormat;
	}

	public function getPageMargins(): string
	{
		return $this->pageMargins;
	}

	/**
	 * Gets margins as array
	 *
	 * @return array<mixed>
	 */
	public function getMargins(): array
	{
		$margins = explode(',', $this->pageMargins);

		$dictionary = ['top', 'right', 'bottom', 'left', 'header', 'footer'];

		$marginsOut = [];
		foreach ($margins as $key => $val) {
			$marginsOut[$dictionary[$key]] = (int) $val;
		}

		return $marginsOut;
	}

	/**
	 * @throws InvalidStateException
	 * @throws InvalidArgumentException
	 */
	public function setPageMargins(string $pageMargins): void
	{
		if ($this->mPDF !== null) {
			throw new InvalidStateException('mPDF instance already created. Set page margins before calling getMPDF');
		}

		$margins = explode(',', $pageMargins);
		if (count($margins) !== 6) {
			throw new InvalidArgumentException('You must specify all margins! For example: 16,15,16,15,9,9');
		}

		foreach ($margins as $val) {
			$val = (int) $val;
			if ($val < 0) {
				throw new InvalidArgumentException('Margin must not be negative number!');
			}
		}

		$this->pageMargins = $pageMargins;
	}

	/**
	 * WARNING: internally creates mPDF instance, setting some properties after calling this method
	 * may cause an Exception
	 *
	 * @throws FileNotFoundException
	 * @throws PdfParserExceptionAlias
	 */
	public function setBackgroundTemplate(string $pathToBackgroundTemplate): void
	{
		if (!file_exists($pathToBackgroundTemplate)) {
			throw new FileNotFoundException("File '" . $pathToBackgroundTemplate . "' not found.");
		}

		$this->backgroundTemplate = $pathToBackgroundTemplate;

		// if background exists, then add it as a background
		$mpdf = $this->getMPDF();
		$pagecount = $mpdf->setSourceFile($this->backgroundTemplate);
		for ($i = 1; $i <= $pagecount; $i++) {
			$tplId = $mpdf->importPage($i);
			$mpdf->useTemplate($tplId);

			if ($i >= $pagecount) {
				continue;
			}

			$mpdf->AddPage();
		}

		$mpdf->page = 1;
	}

	/**
	 * @throws InvalidStateException
	 */
	public function getMPDF(): Mpdf
	{
		if (!$this->mPDF instanceof Mpdf) {
			try {
				$mpdf = new Mpdf($this->getMPDFConfig());
			} catch (MpdfException $e) {
				throw new InvalidStateException('Unable to create Mpdf object', 0, $e);
			}

			$mpdf->showImageErrors = true;

			$this->mPDF = $mpdf;
		}

		return $this->mPDF;
	}

	public function setMPDF(Mpdf $mPDF): self
	{
		$this->mPDF = $mPDF;

		return $this;
	}

	/**
	 * Sends response to output
	 *
	 * @throws MpdfException
	 */
	public function send(IRequest $httpRequest, IResponse $httpResponse): void
	{
		$mpdf = $this->build();
		$mpdf->Output(Strings::webalize($this->documentTitle) . '.pdf', $this->saveMode);
	}

	/**
	 * Save file to target location
	 * Note: $name overrides property $documentTitle
	 *
	 * @param string      $dir path to directory
	 * @throws MpdfException
	 */
	public function save(string $dir, ?string $filename = null): string
	{
		$content = $this->toString();
		$filename = Strings::lower($filename ?? $this->documentTitle);

		if (str_ends_with($filename, '.pdf')) {
			$filename = substr($filename, 0, -4);
		}

		$filename = Strings::webalize($filename, '_') . '.pdf';

		$dir = rtrim($dir, '/') . '/';
		file_put_contents($dir . $filename, $content);

		return $dir . $filename;
	}

	/**
	 * @throws MpdfException
	 */
	public function toString(): string
	{
		$pdf = $this->build();

		return (string) $pdf->Output('', Destination::STRING_RETURN);
	}

	/**
	 * @return mixed[]
	 */
	protected function getMPDFConfig(): array
	{
		$margins = $this->getMargins();

		$mpdfConfig = [
			'mode' => 'utf-8',
			'format' => $this->pageFormat,
			'margin_left' => $margins['left'],
			'margin_right' => $margins['right'],
			'margin_top' => $margins['top'],
			'margin_bottom' => $margins['bottom'],
			'margin_header' => $margins['header'],
			'margin_footer' => $margins['footer'],
			'orientation' => $this->pageOrientation,
		];

		return count($this->mpdfConfig) > 0 ? $this->mpdfConfig + $mpdfConfig : $mpdfConfig;
	}/********************************************************************************
	  *                                  core *
	  ********************************************************************************/


	/********************************************************************************
	 *                                  build *
	 ********************************************************************************/

	/**
	 * Builds final pdf
	 *
	 * @throws InvalidStateException
	 * @throws MissingServiceException
	 * @throws MpdfException
	 */
	private function build(): Mpdf
	{
		if ($this->documentTitle === '') {
			throw new InvalidStateException("Var 'documentTitle' cannot be empty.");
		}

		if ($this->ignoreStylesInHTMLDocument) {
			if (!class_exists('Symfony\Component\DomCrawler\Crawler')) {
				throw new MissingServiceException(
					"Class 'Symfony\\Component\\DomCrawler\\Crawler' not found. Try composer-require 'symfony/dom-crawler'."
				);
			}

			if (!class_exists(CssSelectorConverter::class)) {
				throw new MissingServiceException(
					"Class 'Symfony\\Component\\CssSelector\\CssSelectorConverter' not found. Try composer-require 'symfony/css-selector'."
				);
			}
		}

		if ($this->generatedFile instanceof Mpdf) { // singleton
			return $this->generatedFile;
		}

		if ($this->source instanceof Template) {
			try {
				/** @noinspection PhpMethodParametersCountMismatchInspection */
				$html = $this->source->__toString(true);
			} catch (Throwable $e) {
				throw new InvalidStateException('Template rendering failed', 0, $e);
			}
		} else {
			$html = $this->source;
		}

		// Fix: $html can't be empty (mPDF generates Fatal error)
		if ($html === '') {
			$html = '<html lang=""><body></body></html>';
		}

		$mpdf = $this->getMPDF();
		$mpdf->biDirectional = $this->multiLanguage;
		$author = $this->mpdfConfig['author'] ?? $this->documentAuthor;

		$mpdf->author = $author;

		if (count($this->mpdfConfig) > 0) {
			foreach ($this->mpdfConfig as $key => $value) {
				// @phpstan-ignore-next-line
				$mpdf->$key = $value;
			}
		}

		$mpdf->SetTitle($this->documentTitle);
		$mpdf->SetDisplayMode($this->displayZoom, $this->displayLayout);

		// Add styles
		if ($this->styles !== '') {
			$mpdf->WriteHTML($this->styles, HTMLParserMode::HEADER_CSS);
		}

		// copied from mPDF -> removes comments
		$html = preg_replace('/<!--mpdf/i', '', $html);

		if ($html !== null) {
			$html = preg_replace('/mpdf-->/i', '', $html);
		}

		if ($html !== null) {
			$html = preg_replace('/<\!\-\-.*?\-\->/s', '', $html);
		}

		// @see: https://mpdf.github.io/reference/mpdf-functions/writehtml.html
		if ($this->ignoreStylesInHTMLDocument) {
			// deletes all <style> tags

			$crawler = new Crawler($html);
			foreach ($crawler->filter('style') as $child) {
				/** @var DOMElement $child */
				$parentNode = $child->parentNode;
				/** @var DOMElement $parentNode */
				$parentNode->removeChild($child);
			}

			$html = $crawler->html();

			$mode = HTMLParserMode::HTML_BODY; // If <body> tags are found, all html outside these tags are discarded, and the rest is parsed as content for the document. If no <body> tags are found, all html is parsed as content. Prior to mPDF 4.2 the default CSS was not parsed when using mode #2
		} else {
			$mode = HTMLParserMode::DEFAULT_MODE; // Parse all: HTML + CSS
		}

		// Add content
		$mpdf->WriteHTML((string) $html, $mode);

		$mpdf->page = count($mpdf->pages); //set pointer to last page to force render of all pages
		$this->onBeforeComplete($mpdf);
		$this->generatedFile = $mpdf;

		return $this->generatedFile;
	}/********************************************************************************
	  *                                  output *
	  ********************************************************************************/

	/**
	 * Return generated PDF as a string
	 */
	public function __toString(): string
	{
		$string = '';
		try {
			$string = $this->toString();
		} catch (MpdfException $e) {
			trigger_error(
				'Exception in ' . __METHOD__ . '(): ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(),
				E_USER_ERROR
			);
		}

		return $string;
	}

}
