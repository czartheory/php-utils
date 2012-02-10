<?php

namespace CzarTheory\Utilities;

/**
 * Allows for image resizing with different formats using the GD library.
 *
 * @copyright   Copyright (c) 2011 by CzarTheory, LLC.  All Rights Reserved.
 * @author      Jason Maurer <jasondmaurer@gmail.com>
 */
class ImageResize
{

	/** @var string */
	protected $_sourceFile;

	/** @var int */
	protected $_newWidth;

	/** @var int */
	protected $_newHeight;

	/** @var array */
	protected $_bgColor = null;

	/** @var string */
	protected $_writeType;

	/** @var int */
	protected $_quality = 95;

	/** @var resource */
	protected $_sourceImage;

	public function __construct($sourceFile, $writeType = 'jpg')
	{
		/* Determine image type from the extension */
		$ext = explode(".",$sourceFile);
		$imageType = $ext[count($ext)-1];

		switch ($imageType) {
			case 'jpg':
				$this->_sourceImage = imagecreatefromjpeg($sourceFile);
				break;
			case 'png':
				$this->_sourceImage = imagecreatefrompng($sourceFile);
				break;
			case 'gif':
				$this->_sourceImage = imagecreatefromgif($sourceFile);
				break;
			case 'wbmp':
				$this->_sourceImage = imagecreatefromwbmp($sourceFile);
				break;
			default:
				throw new exception('That filetype is not allowed!');
		}

		$this->_sourceFile = $sourceFile;
		$this->_writeType = $writeType;
	}

	/**
     * Determines the orientation of a given size
	 *
     * @param array $size The width and height
     * @return array X/Y and Width/Height for a resized image
     */
	protected function _checkOrientation($size)
	{
		if ($size[0] >= $size[1]) { /* Landscape */
			$width = $this->_newWidth;
			$height = round(($size[1]/$size[0])*$width);
			$y = ($this->_newWidth - $height) / 2;
			$x = 0;
		} else { /* Portrait */
			$height = $this->_newHeight;
			$width = round(($size[0]/$size[1])*$height);
			$x = ($this->_newHeight - $width) / 2;
			$y = 0;
		}

		$orient = array($x,$y,$width,$height);
		return $orient;
	}

	public function setBgColor(array $bgColor)
	{
		$this->_bgColor = $bgColor;
	}

	public function setQuality($quality)
	{
		if ($quality >= 0 && $quality <= 100) {
			$this->_quality = $quality;
		} else {
			$this->_quality = 100;
		}
	}

	/**
	 * Function to resize a given image. If the given image doesn't conform to the
	 * newly given width/height, it keeps its aspect ratio and is put inside an
	 * image container with the given background color.
	 *
	 * @param string $sourceFile Location of the original image being used
	 * @param string $newFile Location of the new image
	 * @param int $newWidth Width of the new image container
	 * @param int $newHeight Height of the new image container
	 * @param array $bgColor Background color of new image container
	 * @param string $writeType Type of file to write generated image as
	 */
	public function resize($newWidth, $newHeight)
	{
		$this->_newWidth = $newWidth;
		$this->_newHeight = $newHeight;

		/* Create a blank image */
		$img = imagecreatetruecolor($newWidth, $newHeight);
		if ($this->_bgColor !== null) {
			$color = imagecolorallocate($img, $this->_bgColor[0], $this->_bgColor[1], $this->_bgColor[2]);
			imagefill($img, 0, 0, $color);
		}

		/* Get size of submitted image and check its orientation */
		$size = getimagesize($this->_sourceFile);
		$orient = $this->_checkOrientation($size);

		/* Merge blank image created and submitted image */
		imagecopyresampled(
				$img,$this->_sourceImage, /* dst_image, src_image */
				$orient[0],$orient[1], /* dst_x, dst_y */
				0,0, /* src_x, src_y */
				$orient[2],$orient[3], /* dst_w, dst_h */
				$size[0],$size[1] /* src_w, src_h */
		);

		$newFile = explode(".",$this->_sourceFile);
		array_pop($newFile);
		$newFile = implode(".",$newFile);

		/* Write final image and destroy images from memory */
		switch ($this->_writeType) {
			case 'jpg':
				imagejpeg($img,$newFile."_".$this->_newWidth.".jpg",$this->_quality);
				break;
			case 'png':
				imagepng($img,$newFile."_".$this->_newWidth.".png",$this->_quality);
				break;
			case 'gif':
				imagegif($img,$newFile."_".$this->_newWidth.".gif",$this->_quality);
				break;
			case 'wbmp':
				imagewbmp($img,$newFile."_".$this->_newWidth.".wbmp",$this->_quality);
				break;
		}

		imagedestroy($img);
		imagedestroy($this->_sourceImage);
	}

}

?>