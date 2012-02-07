<?php

namespace CzarTheory\Utilities;

/**
 * Allows for image resizing with different formats using the GD library.
 *
 * @copyright   Copyright (c) 2011 by CzarTheory, LLC.  All Rights Reserved.
 * @author      Jason Muarer <jasondmaurer@gmail.com>
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
	protected $_bgColor;

	/** @var int */
	protected $_quality = 95;

	/**
     * Determines the orientation of a given size
	 *
     * @param array $size The width and height
     * @return array X/Y and Width/Height for a resized image
     */
	private function _checkOrientation($size)
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

	/**
	 * Creates a new image in memory depending on the filetype
	 *
	 * @param string $type The image type
	 * @return resource The newly created image
	 */
	private function _resize($type)
	{
		switch ($type) {
			case 'jpg':
				$pic = imagecreatefromjpeg($this->_sourceFile);
				break;
			case 'png':
				$pic = imagecreatefrompng($this->_sourceFile);
				break;
			case 'gif':
				$pic = imagecreatefromgif($this->_sourceFile);
				break;
			case 'wbmp':
				$pic = imagecreatefromwbmp($this->_sourceFile);
				break;
		}

		return $pic;
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
	public function resize($sourceFile, $newFile, $newWidth, $newHeight, $bgColor = array(255,255,255), $writeType = 'jpg')
	{
		$this->_sourceFile = $sourceFile;
		$this->_newWidth = $newWidth;
		$this->_newHeight = $newHeight;

		/* Determine image type from the extension */
		$ext = explode(".",$sourceFile);
		$n = count($ext)-1;
		$imageType = $ext[$n];

		/* Create a blank image */
		$img = imagecreatetruecolor($this->_newWidth,$this->_newHeight);
		$color = imagecolorallocate($img, $bgColor[0], $bgColor[1], $bgColor[2]);
		imagefill($img, 0, 0, $color);

		/* Get size of submitted image and check its orientation */
		$size = getimagesize($this->_sourceFile);
		$orient = $this->_checkOrientation($size);

		/* Use GD library to resize depending on image type */
		switch ($imageType) {
			case 'jpg':
				$pic = $this->_resize('jpg');
				break;
			case 'png':
				$pic = $this->_resize('png');
				break;
			case 'gif':
				$pic = $this->_resize('gif');
				break;
			case 'wbmp':
				$pic = $this->_resize('wbmp');
				break;
		}

		/* Merge blank image created and submitted image */
		imagecopyresampled(
				$img,$pic, /* dst_image, src_image */
				$orient[0],$orient[1], /* dst_x, dst_y */
				0,0, /* src_x, src_y */
				$orient[2],$orient[3], /* dst_w, dst_h */
				$size[0],$size[1] /* src_w, src_h */
		);

		/* Write final image and destroy images from memory */
		switch ($writeType) {
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
		imagedestroy($pic);
	}

}

?>