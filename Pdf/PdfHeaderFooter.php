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
 * Description of PdfHeaderFooter
 *
 * @author matthew larson
 *
 * This interface describes the needed methods for creating custom
 * headers and footers.
 */
interface PdfHeaderFooter {

	/**
	 * this function is usually run internal to a PdfDocument object
	 * the PDF document will run the render() method on each page
	 */
	public function render(PdfDocument $pdf);

	public function setHeight(int $height);
	public function getHeight();

	public function setY(int $yPos);
	public function getY();

	public function setTitle(string $title);
	public function setSubtitle(string $subtitle);
}

?>
