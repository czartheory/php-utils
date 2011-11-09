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

require_once '{TCPDF_PATH}';


/**
 * Description of TcpdfDocument
 *
 * @author matthew larson
 *
 * An implementation of PdfDocument that uses the
 * TCPDF library
 */
class TcpdfDocument implements PdfDocument {

	/** @var PdfHeaderFooter */ protected $_footer = null;
	/** @var PdfHeaderFooter */ protected $_header = null;
	
	/** @var PdfChapter */ protected $_currentChapter = null;
	/** @var int */ protected $_chapterCount = 0;
	
	/** @var string */ protected $_title;

	/** @var bool */ protected $_newPageOnChapters = true;

	/** @var real */ protected $_minY = 1;
	/** @var real */ protected $_maxY = 10;
	/** @var real */ protected $_currentY = 1;

	/** @var TCPDF */ protected $_document;

	/** @var string */ protected $_currentFont;
	/** @var array */ protected $_currentLineStyle = null;
	/** @var array */ protected $_colors = array();


	//------------------------ Implemented Functions for PdfDocument -----------------------------------------
	public function __construct(){
		$document = new TCPDF('P','in','ANSI_A',true,'UTF-8',false);
		$document->setCreator($creator);
		$document->setAuthor($author);
		$document->setPrintHeader(false);
		$document->setPrintFooter(false);
		$document->SetAutoPageBreak(false);


	}

	//------------------------ Implemented Functions for PdfDocument -----------------------------------------
	public function saveAs(string $fileName){
		if($this->_footer != null) $this->_footer->render ($this);
		$this->_document->Output($fileName,'F');
	}

	public function sendToBrowser(){
		$this->_document->Output($this->_title,'I');
	}

	public function setHeader(PdfHeaderFooter $header){
		$this->_header = $header;
	}

	public function setFooter(PdfHeaderFooter $footer){
		$this->_footer = $footer;
	}

	public function  setTitle(string $title){
		$this->_title = $title;
		$this->_document->SetTitle($title);
	}

	public function  getTitle(){
		return $this->_title;
	}

	public function addChapter(PdfChapter $chapter){
		$this->_currentChapter = $chapter;
		$this->_chapterCount++;
		if($this->_newPageOnChapters) $this->setNewPage();
		$chapter->render($this);
	}

	public function  getCurrentChapter(){
		return $this->_currentChapter;
	}

	public function  getChapterCount(){
		return $this->_chapterCount;
	}

	public function  getCurrentPageAlias(){
		return $this->_document->getAliasNumPage();
	}

	public function  getPageCountAlias(){
		return $this->_document->getAliasNbPages();
	}

	
	public function  startNewPage(){
		$this->_currentY = $this->_minY;

		if($this->_footer != null) $this->_footer->render ($this);

		$this->_document->AddPage();

		if($this->_header != null) $this->_header->render ($this);
	}

	public function addPdfChunk(PdfChunk $chunk){
		$currentYafterChunk = $this->_currentY + $chunk->getHeight();
		if($this->_maxY < $currentYafterChunk) $this->startNewPage();
		$chunk->render($this, $this->_currentY);
		$this->_currentY += $chunk->getHeight();
	}


	public function addBookmark(string $bookmark, int $level = 0){
		$this->_document->Bookmark($bookmark,$level,0);
	}


	public function addFont(string $font, string $fontFile){

	}

	public function setFont(string $font){
		$this->_document->SetFont($font);
	}

	public function addRgbColor(string $colorName, int $red, int $green, int $blue){
		$this->_colors[$colorName] = array($red,$green,$blue);
	}

	public function addCmykColor(string $colorName, int $cyan, int $magenta, int $yellow, int $black){
		$this->_colors[$colorName] = array($cyan,$magenta,$yellow,$black);
	}

	public function setTextColor(string $colorName){
		$color = $this->_colors[$colorName];
		if(count($color) == 3) { //rgb color
			$this->_document->SetTextColor($color[0],$color[1],$color[2]);
		}
		else { //cmyk color
			$this->_document->SetTextColor($color[0],$color[1],$color[2],$color[3]);
		}
	}

	public function setLineColor(string $colorName){
		$color = $this->_colors[$colorName];
		if(count($color) == 3) { //rgb color
			$this->_document->SetDrawColor($color[0],$color[1],$color[2]);
		}
		else { //cmyk color
			$this->_document->SetDrawColor($color[0],$color[1],$color[2],$color[3]);
		}
	}

	public function setFillColor(string $colorName){
		$color = $this->_colors[$colorName];
		if(count($color) == 3) { //rgb color
			$this->_document->SetFillColor($color[0],$color[1],$color[2]);
		}
		else { //cmyk color
			$this->_document->SetFillColor($color[0],$color[1],$color[2],$color[3]);
		}
	}

	public function setLineStyle(PdfLineStyle $style){
		$this->_currentLineStyle = $style->toArray();
	}

	public function addTextLine(string $text, real $fontSize, real $x, real $y){

	}

	public function addParagraph(string $text,
								 real $fontSize,
								 real $lineHeight,
								 real $x, real $y,
								 real $width, real $height,
								 string $justify = 'left')
	{

	}


	//************These methods need to add items relative to a PdfDocument's current Y************
	public function drawLine(real $startX, real $startY, real $endX, real $endY){
		$curY = $this->_currentY;
		$this->_document->Line($startX,$startY+$curY,$endX, $endY+$curY,$this->_currentLineStyle);
	}

	public function drawRect(real $x, real $y, real $width, real $height){

	}

	public function drawRoundRect(real $x, real $y, real $width, real $height, real $radius){

	}

	public function drawPolyLine(array $points){

	}

	public function drawPolyShape(array $points){

	}

	public function drawCircle(real $x, real $y, real $radius){

	}

}

?>
