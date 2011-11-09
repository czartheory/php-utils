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
 * Description of PdfChunk
 *
 * @author matthew larson
 *
 * This interface describes the needed methods for adding elements
 * to a pdf file
 */
interface PdfChunk {

	public function setHeight(int $height);
	public function getHeight();

	public function setTopMargin(int $margin);
	public function setBottomMargin(int $margin);

	/**
	 * this function is usually run internal to a PdfDocument object
	 * the PDF document will run the render() method for each of its chunks
	 */
	public function render(PdfDocument $pdf);
}
?>
