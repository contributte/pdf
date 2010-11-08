<?php

/**
 * PdfResponse
 * -----------
 * Wrapper of mPDF.
 * Generate PDF from Nette Framework in one line.
 *
 * @author     Jan Kuchař
 * @copyright  Copyright (c) 2010 Jan Kuchař (http://mujserver.net)
 * @license    LGPL
 * @link       http://addons.nettephp.com/cs/pdfresponse
 */


/**
 * Extended version of mPDF
 *  - added support for JavaScript
 *  - shortcut for opening print dialog
 */
class mPDFExtended extends mPDF {

	// <editor-fold defaultstate="collapsed" desc="JavaScript support - use $mpf->IncludeJS($yourScript)">
	var $javascript="";
	var $n_js;

	function IncludeJS($script) {
		$this->javascript.=$script;
	}

	function _putjavascript() {
		$this->_newobj();
		$this->n_js=$this->n;
		$this->_out('<<');
		$this->_out('/Names [(EmbeddedJS) '.($this->n+1).' 0 R]');
		$this->_out('>>');
		$this->_out('endobj');
		$this->_newobj();
		$this->_out('<<');
		$this->_out('/S /JavaScript');
		$this->_out('/JS '.$this->_textstring($this->javascript));
		$this->_out('>>');
		$this->_out('endobj');
	}

	function _putresources() {
		parent::_putresources();
		if (!empty($this->javascript)) {
			$this->_putjavascript();
		}
	}

	function _putcatalog() {
		parent::_putcatalog();
		if (!empty($this->javascript)) {
			$this->_out('/Names <</JavaScript '.($this->n_js).' 0 R>>');
		}
	}
	// </editor-fold>

	function OpenPrintDialog() {
		$this->IncludeJS("print();");
	}

} 