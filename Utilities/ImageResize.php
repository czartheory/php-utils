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

	/** @var resource */
	protected $_sourceImage;

	/** @var array */
	protected $_originalSize;

	/** @var array */
	protected $_bgColor = null;

	/** @var int */
	protected $_quality = 95;

	public function __construct($sourceFile)
	{
		if(!is_readable($sourceFile)){
			throw new \InvalidArgumentException('This file is unreadable: ' . $sourceFile);
		}

		/* Determine image type from the extension */
		$ext = explode(".", $sourceFile);
		$imageType = $ext[count($ext) - 1];

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
				throw new \exception('That filetype is not allowed!');
		}

		$this->_originalSize = getimagesize($sourceFile);
		$this->_findAppropriateBgColor();
	}

	/**
	 * Finds an appropriate color to use for the background
	 * Based upon the average of a sampling of pixels from the original image
	 */
	protected function _findAppropriateBgColor()
	{
		$img = $this->_sourceImage;
		$size = $this->_originalSize;
		$right = $size[0] - 1;
		$botm = $size[1] - 1;

		$raw = imagecolorat($img, 0, 0);
		$ul = array(
			($raw >> 16) & 0xFF,
			($raw >> 8) & 0xFF,
			$raw & 0xFF,
		);

		$raw = imagecolorat($img, $right, 0);
		$ur = array(
			($raw >> 16) & 0xFF,
			($raw >> 8) & 0xFF,
			$raw & 0xFF,
		);

		$raw = imagecolorat($img, 0, $botm);
		$ll = array(
			($raw >> 16) & 0xFF,
			($raw >> 8) & 0xFF,
			$raw & 0xFF,
		);

		$raw = imagecolorat($img, $right, $botm);
		$lr = array(
			($raw >> 16) & 0xFF,
			($raw >> 8) & 0xFF,
			$raw & 0xFF,
		);


		$color = array(
			($ul[0] + $ur[0] + $ll[0] + $lr[0]) / 4,
			($ul[1] + $ur[1] + $ll[1] + $lr[1]) / 4,
			($ul[2] + $ur[2] + $ll[2] + $lr[2]) / 4,
		);

		$this->_bgColor = $color;
	}


	/**
	 * Determines the orientation of a given size
	 */
	protected function _checkOrientation($newWidth, $newHeight)
	{
		$size = $this->_originalSize;
		if ($size[0] >= $size[1]) { // Landscape
			$width = $newWidth;
			$height = round(($size[1] / $size[0]) * $width);
			$y = ($newWidth - $height) / 2;
			$x = 0;
		} else { // Portrait
			$height = $newHeight;
			$width = round(($size[0] / $size[1]) * $height);
			$x = ($newHeight - $width) / 2;
			$y = 0;
		}

		return array($x, $y, $width, $height);
	}

	/**
	 * Sets the background color of exported images
	 * @param array $bgColor RGB color
	 */
	public function setOutputFillColor(array $bgColor)
	{
		$this->_bgColor = $bgColor;
	}

	/**
	 * Sets the output quality of the newly resized image
	 *
	 * @param int $quality Image output quality
	 */
	public function setQuality($quality)
	{
		if ($quality <= 0) {
			$this->_quality = 0;
		} elseif ($quality >= 100) {
			$this->_quality = 100;
		} else {
			$this->_quality = $quality;
		}
	}

	/**
	 * Function to resize a given image. If the given image doesn't conform to the
	 * newly given width/height, it keeps its aspect ratio and is put inside an
	 * image container with the given background color.
	 *
	 * @param int $outWidth Width of the new image container
	 * @param int $outHeight Height of the new image container
	 * @param string $outFile the filename to output (without the extension);
	 * @param string $extension the file extension required
	 */
	public function resizeToBox($outWidth, $outHeight, $outFile, $extension)
	{
		/* Create a blank image */
		$outImage = imagecreatetruecolor($outWidth, $outHeight);
		if ($this->_bgColor !== null) {
			$color = imagecolorallocate($outImage, $this->_bgColor[0], $this->_bgColor[1], $this->_bgColor[2]);
			imagefill($outImage, 0, 0, $color);
		}

		/* Get size of submitted image and check its orientation */
		$orient = $this->_checkOrientation($outWidth,$outHeight);
		$size = $this->_originalSize;

		/* Merge blank image created and submitted image */
		imagecopyresampled(
			$outImage, //Destination
			$this->_sourceImage, //Source
			$orient[0], //Dest-X
			$orient[1], //Dext-Y
			0, //Source-x
			0, //Source-y
			$orient[2], //Dest Width
			$orient[3], //Dest Height
			$size[0], //Source Width
			$size[1] //Source Height
		);

		/* Write final image and destroy images from memory */
		switch ($extension) {
			case 'jpeg':
			case 'jpg':
				imagejpeg($outImage, $outFile . ".jpg", $this->_quality);
				break;
			case 'png':
				imagepng($outImage, $outFile . ".png", $this->_quality);
				break;
			case 'gif':
				imagegif($outImage, $outFile . ".gif", $this->_quality);
				break;
			case 'wbmp':
				imagewbmp($outImage, $outFile . ".wbmp", $this->_quality);
				break;
			default:
				throw new \InvalidArgumentException("Unrecognized Output Extension: $extension");
		}
		imagedestroy($outImage);
	}

}
?>