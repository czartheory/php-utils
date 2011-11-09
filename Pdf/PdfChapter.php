<?php
/**
 * Czar Theory
 *
 * LICENSE
 *
 * This source file is the sole ownership of Czar Theory LLC. It is protected
 * under copyright law. Any use of this code without consent of Czar Theory LLC
 * is stricly prohibited.
 *
 * @category CzarTheory
 * @package Pdf
 * @copyright (c) 2011 Czar Theory LLC
 *
 */

namespace CzarTheory\Pdf;

/**
 * Description of PdfChapter
 *
 * @author matthew larson
 *
 * This class is used to group chunks
 * a pdf document.
 */
abstract class PdfChapter {

	protected $_itemList = array();
	/** @var PdfChunk */ protected $_introChunk;


	public function  __construct(PdfChunk $introChunk){
		$this->_introChunk = $introChunk;
	}

	public function addPageBreak(){
		array_push($this->_itemList, null);
	}

	public function addChunk(PdfChunk $chunk){
		array_push($chunk);
	}

	/**
	 * this function is usually run internal to a PdfDocument object
	 * the PDF document will run the render() method for each of its chunks
	 */
	public function render(PdfDocument $pdf){

		$pdf->addPdfChunk($this->_introChunk);
		$this->bookmark($pdf);

		foreach($this->_itemList as $item){
			if($item == null) $pdf->startNewPage();
			else $pdf->addPdfChunk ($item);
		}
	}

	abstract protected function bookmark(PdfDocument $pdf);
}


?>
