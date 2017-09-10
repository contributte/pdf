<?php

namespace Joseki\Application\Responses;

use mPDF;
use Nette;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\FileNotFoundException;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Utils\Strings;
use Symfony\Component\DomCrawler\Crawler;

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
 *
 *
 * @property string $saveMode
 * @property string $pageOrientation
 * @property string $pageFormat
 * @property string $pageMargins
 * @property string $documentAuthor
 * @property string $documentTitle
 * @property string|int $displayZoom
 * @property string $displayLayout
 * @property bool $multiLanguage
 * @property bool $ignoreStylesInHTMLDocument
 * @method onBeforeComplete($mpdf) @internal
 */
class PdfResponse extends Nette\Object implements Nette\Application\IResponse
{
    /** possible save modes */
    const INLINE = "I";

    const DOWNLOAD = "D";

    /**  Portrait page orientation */
    const ORIENTATION_PORTRAIT = "P";

    /** Landscape page orientation */
    const ORIENTATION_LANDSCAPE = "L";

    /** @see https://mpdf.github.io/reference/mpdf-functions/setdisplaymode.html */
    const ZOOM_DEFAULT = "default"; // User’s default setting in Adobe Reader
    const ZOOM_FULLPAGE = "fullpage"; // Fit a whole page in the screen
    const ZOOM_FULLWIDTH = "fullwidth"; // Fit the width of the page in the screen
    const ZOOM_REAL = "real"; // Display at real size

    /** @see https://mpdf.github.io/reference/mpdf-functions/setdisplaymode.html */
    const LAYOUT_SINGLE = "single"; // Display one page at a time
    const LAYOUT_CONTINUOUS = "continuous"; // Display the pages in one column
    const LAYOUT_TWO = "two"; // Display the pages in two columns (first page determined by document direction (e.g. RTL))
    const LAYOUT_TWOLEFT = "twoleft"; // Display the pages in two columns, with the first page displayed on the left side (mPDF >= 5.2)
    const LAYOUT_TWORIGHT = "tworight"; // Display the pages in two columns, with the first page displayed on the right side (mPDF >= 5.2)
    const LAYOUT_DEFAULT = "default"; // User’s default setting in Adobe Reader

    /** @var array onBeforeComplete event */
    public $onBeforeComplete = array();

    /** Additional stylesheet as a html string */
    public $styles = "";

    /** @var string */
    private $documentAuthor = "Nette Framework - Pdf response";

    /** @var string */
    private $documentTitle = "New document";

    /** @var string|int */
    private $displayZoom = self::ZOOM_DEFAULT;

    /** @var string */
    private $displayLayout = self::LAYOUT_DEFAULT;

    /** @var bool */
    private $multiLanguage = false;

    /** @var bool, REQUIRES symfony/dom-crawler package */
    private $ignoreStylesInHTMLDocument = false;

    /** @var  string|Template */
    private $source;

    /** @var string save mode */
    private $saveMode = self::DOWNLOAD;

    /** @var string path to (PDF) file */
    private $backgroundTemplate;

    /** @var string ORIENTATION_PORTRAIT or ORIENTATION_LANDSCAPE */
    private $pageOrientation = self::ORIENTATION_PORTRAIT;

    /** @var string see second parameter ($format) at https://mpdf.github.io/reference/mpdf-functions/mpdf.html */
    private $pageFormat = "A4";

    /** @var string margins: top, right, bottom, left, header, footer */
    private $pageMargins = "16,15,16,15,9,9";

    /** @var mPDF */
    private $mPDF = null;

    /** @var  mPDF */
    private $generatedFile;

    /************************************ properties **************************************/

    /**
     * @return string
     */
    public function getDocumentAuthor()
    {
        return $this->documentAuthor;
    }



    /**
     * @param string $documentAuthor
     */
    public function setDocumentAuthor($documentAuthor)
    {
        $this->documentAuthor = (string)$documentAuthor;
    }



    /**
     * @return string
     */
    public function getDocumentTitle()
    {
        return $this->documentTitle;
    }



    /**
     * @param string $documentTitle
     */
    public function setDocumentTitle($documentTitle)
    {
        $this->documentTitle = (string)$documentTitle;
    }



    /**
     * @return string
     */
    public function getDisplayZoom()
    {
        return $this->displayZoom;
    }



    /**
     * @param string $displayZoom
     */
    public function setDisplayZoom($displayZoom)
    {
        if (!in_array($displayZoom, array(self::ZOOM_DEFAULT, self::ZOOM_FULLPAGE, self::ZOOM_FULLWIDTH, self::ZOOM_REAL)) && $displayZoom <= 0) {
            throw new InvalidArgumentException("Invalid zoom '$displayZoom', use PdfResponse::ZOOM_* constants or o positive integer.");
        }
        $this->displayZoom = $displayZoom;
    }



    /**
     * @return mixed
     */
    public function getDisplayLayout()
    {
        return $this->displayLayout;
    }



    /**
     * @param mixed $displayLayout
     */
    public function setDisplayLayout($displayLayout)
    {
        if (!in_array(
                $displayLayout,
                array(self::LAYOUT_DEFAULT, self::LAYOUT_CONTINUOUS, self::LAYOUT_SINGLE, self::LAYOUT_TWO, self::LAYOUT_TWOLEFT, self::LAYOUT_TWORIGHT)
            ) && $displayLayout <= 0
        ) {
            throw new InvalidArgumentException("Invalid layout '$displayLayout', use PdfResponse::LAYOUT* constants.");
        }
        $this->displayLayout = (string)$displayLayout;
    }



    /**
     * @return boolean
     */
    public function isMultiLanguage()
    {
        return $this->multiLanguage;
    }



    /**
     * @param boolean $multiLanguage
     */
    public function setMultiLanguage($multiLanguage)
    {
        $this->multiLanguage = (bool)$multiLanguage;
    }



    /**
     * @return boolean
     */
    public function isIgnoreStylesInHTMLDocument()
    {
        return $this->ignoreStylesInHTMLDocument;
    }



    /**
     * @param boolean $ignoreStylesInHTMLDocument
     */
    public function setIgnoreStylesInHTMLDocument($ignoreStylesInHTMLDocument)
    {
        $this->ignoreStylesInHTMLDocument = (bool)$ignoreStylesInHTMLDocument;
    }



    /**
     * @return string
     */
    public function getSaveMode()
    {
        return $this->saveMode;
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
            throw new InvalidArgumentException("Invalid mode '$saveMode', use PdfResponse::INLINE or PdfResponse::DOWNLOAD instead.");
        }
        $this->saveMode = $saveMode;
    }



    /**
     * @return string
     */
    public function getPageOrientation()
    {
        return $this->pageOrientation;
    }



    /**
     * @param string $pageOrientation
     * @throws InvalidStateException
     * @throws InvalidArgumentException
     */
    public function setPageOrientation($pageOrientation)
    {
        if ($this->mPDF) {
            throw new InvalidStateException('mPDF instance already created. Set page orientation before calling getMPDF');
        }
        if (!in_array($pageOrientation, array(self::ORIENTATION_PORTRAIT, self::ORIENTATION_LANDSCAPE))) {
            throw new InvalidArgumentException('Unknown page orientation');
        }
        $this->pageOrientation = $pageOrientation;
    }



    /**
     * @return string
     */
    public function getPageFormat()
    {
        return $this->pageFormat;
    }



    /**
     * @param string $pageFormat
     * @throws InvalidStateException
     */
    public function setPageFormat($pageFormat)
    {
        if ($this->mPDF) {
            throw new InvalidStateException('mPDF instance already created. Set page format before calling getMPDF');
        }
        $this->pageFormat = $pageFormat;
    }



    /**
     * @return string
     */
    public function getPageMargins()
    {
        return $this->pageMargins;
    }



    /**
     * Gets margins as array
     * @return array
     */
    public function getMargins()
    {
        $margins = explode(",", $this->pageMargins);

        $dictionary = array("top", "right", "bottom", "left", "header", "footer");

        $marginsOut = array();
        foreach ($margins AS $key => $val) {
            $marginsOut[$dictionary[$key]] = (int)$val;
        }

        return $marginsOut;
    }



    /**
     * @param string $pageMargins
     * @throws InvalidStateException
     * @throws InvalidArgumentException
     */
    public function setPageMargins($pageMargins)
    {
        if ($this->mPDF) {
            throw new InvalidStateException('mPDF instance already created. Set page margins before calling getMPDF');
        }

        $margins = explode(",", $pageMargins);
        if (count($margins) !== 6) {
            throw new InvalidArgumentException("You must specify all margins! For example: 16,15,16,15,9,9");
        }

        foreach ($margins AS $val) {
            $val = (int)$val;
            if ($val < 0) {
                throw new InvalidArgumentException("Margin must not be negative number!");
            }
        }

        $this->pageMargins = $pageMargins;
    }



    /**
     * WARNING: internally creates mPDF instance, setting some properties after calling this method
     * may cause an Exception
     *
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
     * @throws InvalidStateException
     * @return mPDF
     */
    public function getMPDF()
    {
        if (!$this->mPDF instanceof mPDF) {
            $margins = $this->getMargins();

            $mpdf = new mPDF(
                'utf-8', // string $codepage
                $this->pageFormat, // mixed $format
                '', // float $default_font_size
                '', // string $default_font
                $margins["left"], // float $margin_left
                $margins["right"], // float $margin_right
                $margins["top"], // float $margin_top
                $margins["bottom"], // float $margin_bottom
                $margins["header"], // float $margin_header
                $margins["footer"], // float $margin_footer
                $this->pageOrientation
            );

            $this->mPDF = $mpdf;
        }

        return $this->mPDF;
    }



    /*********************************** core **************************************/

    /**
     * @param Template|string $source
     * @throws InvalidArgumentException
     */
    public function __construct($source)
    {
        if (!($source instanceof Template) && !is_string($source)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid source type. Expected (html) string or instance of Nette\Bridges\ApplicationLatte\Template, but "%s" given.',
                    is_object($source) ? get_class($source) : gettype($source)
                )
            );
        }
        $this->source = $source;
    }



    /*********************************** build **************************************/

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
        if ($this->ignoreStylesInHTMLDocument) {
            if (!class_exists('Symfony\Component\DomCrawler\Crawler')) {
                throw new MissingServiceException(
                    "Class 'Symfony\\Component\\DomCrawler\\Crawler' not found. Try composer-require 'symfony/dom-crawler'."
                );
            }
            if (!class_exists('Symfony\Component\CssSelector\CssSelector')) {
                throw new MissingServiceException(
                    "Class 'Symfony\\Component\\CssSelector\\CssSelector' not found. Try composer-require 'symfony/css-selector'."
                );
            }
        }

        if ($this->generatedFile) { // singleton
            return $this->generatedFile;
        }

        if ($this->source instanceof Template) {
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
        $mpdf->showImageErrors = true;

        // Add styles
        if (!empty($this->styles)) {
            $mpdf->WriteHTML($this->styles, 1);
        }

        // copied from mPDF -> removes comments
        $html = preg_replace('/<!--mpdf/i', '', $html);
        $html = preg_replace('/mpdf-->/i', '', $html);
        $html = preg_replace('/<\!\-\-.*?\-\->/s', '', $html);

        // @see: https://mpdf.github.io/reference/mpdf-functions/writehtml.html
        if ($this->ignoreStylesInHTMLDocument) {
            // deletes all <style> tags

            $crawler = new Crawler($html);
            foreach ($crawler->filter('style') as $child) {
                $child->parentNode->removeChild($child);
            }
            $html = $crawler->html();

            $mode = 2; // If <body> tags are found, all html outside these tags are discarded, and the rest is parsed as content for the document. If no <body> tags are found, all html is parsed as content. Prior to mPDF 4.2 the default CSS was not parsed when using mode #2
        } else {
            $mode = 0; // Parse all: HTML + CSS
        }

        // Add content
        $mpdf->WriteHTML($html, $mode);

        $mpdf->page = count($mpdf->pages); //set pointer to last page to force render of all pages
        $this->onBeforeComplete($mpdf);
        $this->generatedFile = $mpdf;

        return $this->generatedFile;
    }



    /*********************************** output **************************************/

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
     * Save file to target location
     * Note: $name overrides property $documentTitle
     *
     * @param string $dir path to directory
     * @param string $filename
     * @return string
     */
    public function save($dir, $filename = null)
    {
        $content = $this->__toString();
        $filename = Strings::lower($filename ?: $this->documentTitle);

        if (Strings::endsWith($filename, ".pdf")) {
            $filename = substr($filename, 0,-4);
        }

        $filename = Strings::webalize($filename, "_") . ".pdf";

        $dir = rtrim($dir, "/") . "/";
        file_put_contents($dir . $filename, $content);

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

}
