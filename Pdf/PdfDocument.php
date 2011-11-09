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
 * Description of PdfDocument
 *
 * @author matthew larson
 *
 * This interface describes the needed methods for creating a pdf document
 * and rendering it to a browser or to a file.
 */
interface PdfDocument{

	public function saveAs(string $fileName);
	public function sendToBrowser();

	public function setHeader(PdfHeaderFooter $header);
	public function setFooter(PdfHeaderFooter $footer);

	public function setTitle(string $title);
	/** @return string */public function getTitle();

	public function addChapter(PdfChapter $chapter);
	/** @return PdfChapter */ public function getCurrentChapter();
	/** @return int */ public function getChapterCount();

	/** @return string */ public function getCurrentPageAlias();
	/** @return string */ public function getPageCountAlias();

	public function startNewPage();

	public function addPdfChunk(PdfChunk $chunk);
	public function addBookmark(string $bookmark, int $level = 0);

	public function addFont(string $font, string $fontFile);
	public function setFont(string $font);

	public function addRgbColor(string $colorName, int $red, int $green, int $blue);
	public function addCmykColor(string $colorName, int $cyan, int $magenta, int $yellow, int $black);

	public function setTextColor(string $colorName);
	public function setLineColor(string $colorName);
	public function setFillColor(string $colorName);

	public function setLineStyle(PdfLineStyle $style);

	public function addTextLine(string $text, real $fontSize, real $x, real $y);
	public function addParagraph(string $text, real $fontSize, real $lineHeight, real $x, real $y, real $width, real $height, string $justify = 'left');


	//These methods need to add items relative to a PdfDocument's current Y
	public function drawLine(real $startX, real $startY, real $endX, real $endY);
	public function drawRect(real $x, real $y, real $width, real $height);
	public function drawRoundRect(real $x, real $y, real $width, real $height, real $radius);
	public function drawPolyLine(array $points);
	public function drawPolyShape(array $points);
	public function drawCircle(real $x, real $y, real $radius);
}

?>
