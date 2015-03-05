<?php
// +----------------------------------------------------------------------
// | Leaps Framework [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011-2014 Leaps Team (http://www.tintsoft.com)
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author XuTongle <xutongle@gmail.com>
// +----------------------------------------------------------------------
namespace Leaps\Image\Adapter;

class Imagick extends \Leaps\Image\Adapter implements \Leaps\Image\AdapterInterface
{
	protected $_version = 0;
	public static function check()
	{
		if (self::$_checked) {
			return true;
		}

		if (! class_exists ( "imagick" )) {
			throw new \Leaps\Image\Exception ( "Imagick is not installed, or the extension is not loaded" );
		}

		if (defined ( "Imagick::IMAGICK_EXTNUM" )) {
			$this->_version = constant ( "Imagick::IMAGICK_EXTNUM" );
		}

		self::$_checked = true;

		return self::$_checked;
	}
	public function __construct($file, $width = null, $height = null)
	{
		if (! self::$_checked) {
			self::check ();
		}
		$this->_file = $file;
		$this->_image = new \Imagick ();
		if (file_exists ( $this->_file )) {
			$this->_realpath = realpath ( $this->_file );
			if (! $this->_image->readImage ( $this->_realpath )) {
				throw new \Leaps\Image\Exception ( "Imagick::readImage " . $this->_file . " failed" );
			}
			if (! $this->_image->getImageAlphaChannel ()) {
				$this->_image->setImageAlphaChannel ( constant ( "Imagick::ALPHACHANNEL_SET" ) );
			}
			if ($this->_type == 1) {
				$image = $this->_image->coalesceImages ();
				$this->_image->clear ();
				$this->_image->destroy ();
				$this->_image = $image;
			}
		} else {
			if (! $width || ! $height) {
				throw new \Leaps\Image\Exception ( "Failed to create image from file " . $this->_file );
			}
			$this->_image->newImage ( $width, $height, new \ImagickPixel ( "transparent" ) );
			$this->_image->setFormat ( "png" );
			$this->_image->setImageFormat ( "png" );
			$this->_realpath = $this->_file;
		}

		$this->_width = $this->_image->getImageWidth ();
		$this->_height = $this->_image->getImageHeight ();
		$this->_type = $this->_image->getImageType ();
		$this->_mime = "image/" . $this->_image->getImageFormat ();
	}
	protected function _resize($width, $height)
	{
		$this->_image->setIteratorIndex ( 0 );
		while ( true ) {
			$this->_image->scaleImage ( $width, $height );
			if (! $this->_image->nextImage ()) {
				break;
			}
		}

		$this->_width = $this->_image->getImageWidth ();
		$this->_height = $this->_image->getImageHeight ();
	}
	protected function _crop($width, $height, $offset_x, $offset_y)
	{
		$image = $this->_image;
		$image->setIteratorIndex ( 0 );
		while ( true ) {
			$image->cropImage ( $width, $height, $offset_x, $offset_y );
			$image->setImagePage ( $width, $height, 0, 0 );
			if (! $image->nextImage ()) {
				break;
			}
		}
		$this->_width = $image->getImageWidth ();
		$this->_height = $image->getImageHeight ();
	}
	protected function _rotate($degrees)
	{
		$this->_image->setIteratorIndex ( 0 );
		$pixel = new \ImagickPixel ();
		while ( true ) {
			$this->_image->rotateImage ( $pixel, $degrees );
			$this->_image->setImagePage ( $this->_width, $this->_height, 0, 0 );
			if (! $this->_image->nextImage ()) {
				break;
			}
		}

		$this->_width = $this->_image->getImageWidth ();
		$this->_height = $this->_image->getImageHeight ();
	}
	protected function _flip($direction)
	{
		$func = "flipImage";
		if ($direction == \Leaps\Image::HORIZONTAL) {
			$func = "flopImage";
		}

		$this->_image->setIteratorIndex ( 0 );

		while ( true ) {
			$this->_image->$func ();
			if (! $this->_image->nextImage ()) {
				break;
			}
		}
	}
	protected function _sharpen($amount)
	{
		$amount = ($amount < 5) ? 5 : $amount;
		$amount = ($amount * 3.0) / 100;

		$this->_image->setIteratorIndex ( 0 );

		while ( true ) {
			$this->_image->sharpenImage ( 0, $amount );
			if (! $this->_image->nextImage ()) {
				break;
			}
		}
	}
	protected function _reflection($height, $opacity, $fade_in)
	{
		if ($this->_version >= 30100) {
			$reflection = clone $this->_image;
		} else {
			$reflection = clone $this->_image->$clone ();
		}

		$reflection->setIteratorIndex ( 0 );

		while ( true ) {
			$reflection->flipImage ();
			$reflection->cropImage ( $reflection->getImageWidth (), $height, 0, 0 );
			$reflection->setImagePage ( $reflection->getImageWidth (), $height, 0, 0 );
			if (! $reflection->nextImage ()) {
				break;
			}
		}

		$pseudo = $fade_in ? "gradient:black-transparent" : "gradient:transparent-black";

		$fade = new \Imagick ();

		$fade->newPseudoImage ( $reflection->getImageWidth (), $reflection->getImageHeight (), $pseudo );

		$opacity /= 100;

		$reflection->setIteratorIndex ( 0 );

		while ( true ) {
			$reflection->compositeImage ( $fade, constant ( "Imagick::COMPOSITE_DSTOUT" ), 0, 0 );
			$reflection->evaluateImage ( constant ( "Imagick::EVALUATE_MULTIPLY" ), $opacity / 100, constant ( "Imagick::CHANNEL_ALPHA" ) );
			if (! $reflection->nextImage ()) {
				break;
			}
		}

		$fade->destroy ();

		$image = new \Imagick ();
		$pixel = new \ImagickPixel ();
		$height = $this->_image->getImageHeight () + $height;

		$this->_image->setIteratorIndex ( 0 );

		while ( true ) {

			$image->newImage ( $this->_width, $height, $pixel );
			$image->setImageAlphaChannel ( constant ( "Imagick::ALPHACHANNEL_SET" ) );
			$image->setColorspace ( $this->_image->getColorspace () );
			$image->setImageDelay ( $this->_image->getImageDelay () );
			$image->compositeImage ( $this->_image, constant ( "Imagick::COMPOSITE_SRC" ), 0, 0 );

			if (! $this->_image->nextImage ()) {
				break;
			}
		}

		$image->setIteratorIndex ( 0 );
		$reflection->setIteratorIndex ( 0 );

		while ( true ) {

			$image->compositeImage ( $reflection, constant ( "Imagick::COMPOSITE_OVER" ), 0, $this->_height );
			$image->setImageAlphaChannel ( constant ( "Imagick::ALPHACHANNEL_SET" ) );
			$image->setColorspace ( $this->_image->getColorspace () );
			$image->setImageDelay ( $this->_image->getImageDelay () );
			$image->compositeImage ( $this->_image, constant ( "Imagick::COMPOSITE_SRC" ), 0, 0 );

			if (! $image->nextImage () || ! $reflection->nextImage ()) {
				break;
			}
		}

		$reflection->destroy ();

		$this->_image->clear ();
		$this->_image->destroy ();

		$this->_image = $image;
		$this->_width = $this->_image->getImageWidth ();
		$this->_height = $this->_image->getImageHeight ();
	}
	protected function _watermark($image, $offset_x, $offset_y, $opacity)
	{
		$opacity = $opacity / 100;

		$watermark = new \Imagick ();

		$watermark->readImageBlob ( $image->render () );
		$watermark->setImageOpacity ( $opacity );

		$this->_image->setIteratorIndex ( 0 );

		while ( true ) {
			$this->_image->compositeImage ( $watermark, constant ( "Imagick::COMPOSITE_OVER" ), $offset_x, $offset_y );
			if (! $this->_image->nextImage ()) {
				break;
			}
		}

		$watermark->clear ();
		$watermark->destroy ();
	}
	protected function _text($text, $offset_x, $offset_y, $opacity, $r, $g, $b, $size, $fontfile)
	{
		$opacity = $opacity / 100;

		$draw = new \ImagickDraw ();

		$color = sprintf ( "rgb(%d, %d, %d)", $r, $g, $b );
		$pixel = new \ImagickPixel ( $color );

		$draw->setFillColor ( $pixel );

		if ($fontfile) {
			$draw->setFont ( $fontfile );
		}

		if ($size) {
			$draw->setFontSize ( $size );
		}

		if ($opacity) {
			$draw->setfillopacity ( $opacity );
		}

		if ($offset_x < 0) {
			$offset_x = abs ( $offset_x );
			if ($offset_y < 0) {
				$offset_y = abs ( $offset_y );
				$gravity = constant ( "Imagick::GRAVITY_SOUTHEAST" );
			} else {
				$gravity = constant ( "Imagick::GRAVITY_NORTHEAST" );
			}
		} else {
			/*
			 * if (y < 0 { where y comes from??
			 * $offset_y = abs(offset_y);
			 * $gravity = constant("Imagick::GRAVITY_SOUTHWEST");
			 * } else {
			 * $gravity = constant("Imagick::GRAVITY_NORTHWEST");
			 * }
			 */
		}

		$draw->setGravity ( $gravity );

		$this->_image->setIteratorIndex ( 0 );

		while ( true ) {
			$this->_image->annotateImage ( $draw, $offset_x, $offset_y, 0, $text );
			if (! $this->_image->nextImage ()) {
				break;
			}
		}
		$draw->destroy ();
	}
	protected function _mask($image)
	{
		// $opacity = opacity / 100; // where opacity comes from?
		$mask = new \Imagick ();

		$mask->readImageBlob ( $image->render () );

		$this->_image->setIteratorIndex ( 0 );

		while ( true ) {
			$this->_image->setImageMatte ( 1 );
			$this->_image->compositeImage ( $mask, constant ( "Imagick::COMPOSITE_DSTIN" ), 0, 0 );
			if (! $this->_image->nextImage ()) {
				break;
			}
		}

		$mask->clear ();
		$mask->destroy ();
	}
	protected function _background($r, $g, $b, $opacity)
	{
		$color = sprintf ( "rgb(%d, %d, %d)", $r, $g, $b );
		$pixel1 = new \ImagickPixel ( $color );
		$opacity = $opacity / 100;

		$pixel2 = new \ImagickPixel ( "transparent" );

		$background = new \Imagick ();
		$this->_image->setIteratorIndex ( 0 );

		while ( true ) {
			$background->newImage ( $this->_width, $this->_height, $pixel1 );
			if (! $background->getImageAlphaChannel ()) {
				$background->setImageAlphaChannel ( constant ( "Imagick::ALPHACHANNEL_SET" ) );
			}
			$background->setImageBackgroundColor ( $pixel2 );
			$background->evaluateImage ( constant ( "Imagick::EVALUATE_MULTIPLY" ), $opacity, constant ( "Imagick::CHANNEL_ALPHA" ) );
			$background->setColorspace ( $this->_image->getColorspace () );
			$background->compositeImage ( $this->_image, constant ( "Imagick::COMPOSITE_DISSOLVE" ), 0, 0 );
			if (! $this->_image->nextImage ()) {
				break;
			}
		}

		$this->_image->clear ();
		$this->_image->destroy ();

		$this->_image = $background;
	}
	protected function _blur($radius)
	{
		$this->_image->setIteratorIndex ( 0 );

		while ( true ) {
			$this->_image->blurImage ( $radius, 100 );
			if (! $this->_image->nextImage ()) {
				break;
			}
		}
	}
	protected function _pixelate($amount)
	{
		$width = $this->_width / $amount;
		$height = $this->_height / $amount;

		$this->_image->setIteratorIndex ( 0 );

		while ( true ) {
			$this->_image->scaleImage ( $width, $height );
			$this->_image->scaleImage ( $this->_width, $this->_height );
			if (! $this->_image->nextImage ()) {
				break;
			}
		}
	}
	protected function _save($file, $quality)
	{
		$ext = pathinfo ( $file, PATHINFO_EXTENSION );
		$this->_image->setFormat ( $ext );
		$this->_image->setImageFormat ( $ext );
		$this->_type = $this->_image->getImageType ();
		$this->_mime = "image/" . $this->_image->getImageFormat ();
		if (strcasecmp ( $ext, "gif" ) == 0) {
			$this->_image->optimizeImageLayers ();
			$fp = fopen ( $file, "w" );
			$this->_image->writeImagesFile ( $fp );
			fclose ( $fp );
			return;
		} else {
			if (strcasecmp ( $ext, "jpg" ) == 0 || strcasecmp ( $ext, "jpeg" ) == 0) {
				$this->_image->setImageCompression ( constant ( "Imagick::COMPRESSION_JPEG" ) );
			}
			$this->_image->setImageCompressionQuality ( $quality );
			$this->_image->writeImage ( $file );
		}
	}
	protected function _render($ext, $quality)
	{
		$image = $this->_image;
		$image->setFormat ( $ext );
		$image->setImageFormat ( $ext );
		$this->_type = $image->getImageType ();
		$this->_mime = "image/" . $image->getImageFormat ();
		if (strcasecmp ( $ext, "gif" ) == 0) {
			$image->optimizeImageLayers ();
			return $image->getImagesBlob ();
		} else {
			if (strcasecmp ( $ext, "jpg" ) == 0 || strcasecmp ( $ext, "jpeg" ) == 0) {
				$image->setImageCompression ( constant ( "Imagick::COMPRESSION_JPEG" ) );
			}
			$image->setImageCompressionQuality ( $quality );
			// return image->getImageBlob(file); where file comes from?
		}
	}
	public function __destruct()
	{
		$image = $this->_image;
		if ($image) {
			$image->clear ();
			$image->destroy ();
		}
	}
}