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
namespace Leaps\Image;

abstract class Adapter
{
	protected $_image;
	protected $_file;
	protected $_realpath;
	protected $_width;
	protected $_height;
	protected $_type;
	protected $_mime;
	protected static $_checked = false;

	/**
	 * Resize the image to the given size
	 *
	 * @param int width
	 * @param int height
	 * @param int master
	 * @return Leaps\Image\Adapter
	 */
	// Leaps\Image::AUTO
	public function resize($width = null, $height = null, $master = 7)
	{
		if ($master == \Leaps\Image::TENSILE) {
			if (! $width || ! $height) {
				throw new \Leaps\Image\Exception ( "width and height must be specified" );
			}
		} else {
			if ($master == \Leaps\Image::AUTO) {
				if (! $width || ! $height) {
					throw new \Leaps\Image\Exception ( "width and height must be specified" );
				}
				$master = ($this->_width / $width) > ($this->_height / $height) ? \Leaps\Image::WIDTH : \Leaps\Image::HEIGHT;
			}
			if ($master == \Leaps\Image::INVERSE) {
				if (! $width || ! $height) {
					throw new \Leaps\Image\Exception ( "width and height must be specified" );
				}
				$master = ($this->_width / $width) > ($this->_height / $height) ? \Leaps\Image::HEIGHT : \Leaps\Image::WIDTH;
			}
			switch ($master) {
				case \Leaps\Image::WIDTH :
					if (! $width) {
						throw new \Leaps\Image\Exception ( "width must be specified" );
					}
					$height = $this->_height * $width / $this->_width;
					break;

				case \Leaps\Image::HEIGHT :
					if (! $height) {
						throw new \Leaps\Image\Exception ( "height must be specified" );
					}
					$width = $this->_width * $height / $this->_height;
					break;
				case \Leaps\Image::PRECISE :
					if (! $width || ! $height) {
						throw new \Leaps\Image\Exception ( "width and height must be specified" );
					}
					$ratio = $this->_width / $this->_height;
					if (($width / $height) > $ratio) {
						$height = $this->_height * $width / $this->_width;
					} else {
						$width = $this->_width * $height / $this->_height;
					}
					break;

				case \Leaps\Image::NONE :

					if (! $width) {
						$width = ( int ) $this->_width;
					}

					if (! $height) {
						$width = ( int ) $this->_height;
					}
					break;
			}
		}

		$width = ( int ) max ( round ( $width ), 1 );
		$height = ( int ) max ( round ( $height ), 1 );
		$this->_resize ( $width, $height );
		return $this;
	}

	/**
	 * Crop an image to the given size
	 *
	 * @param int width
	 * @param int height
	 * @param int offset_x
	 * @param int offset_y
	 * @return Leaps\Image\Adapter
	 */
	public function crop($width, $height, $offset_x = null, $offset_y = null)
	{
		if (! $offset_x) {
			$offset_x = (($this->_width - $width) / 2);
		} else {
			if ($offset_x < 0) {
				$offset_x = $this->_width - $width + $offset_x;
			}
			if ($offset_x > $this->_width) {
				$offset_x = ( int ) $this->_width;
			}
		}
		if (! $offset_y) {
			$offset_y = (($this->_height - $height) / 2);
		} else {
			if ($offset_y < 0) {
				$offset_y = $this->_height - $height + $offset_y;
			}

			if ($offset_y > $this->_height) {
				$offset_y = ( int ) $this->_height;
			}
		}

		if ($width > ($this->_width - $offset_x)) {
			$width = $this->_width - $offset_x;
		}

		if ($height > ($this->_height - $offset_y)) {
			$height = $this->_height - $offset_y;
		}

		$this->_crop ( $width, $height, $offset_y, $offset_y );

		return $this;
	}

	/**
	 * Rotate the image by a given amount
	 *
	 * @param int degrees
	 * @return Leaps\Image\Adapter
	 */
	public function rotate($degrees)
	{
		if ($degrees > 180) {
			$degrees %= 360;
			if ($degrees > 180) {
				$degrees -= 360;
			}
		} else {
			while ( $degrees < - 180 ) {
				$degrees += 360;
			}
		}
		$this->_rotate ( $degrees );
		return $this;
	}

	/**
	 * Flip the image along the horizontal or vertical axis
	 *
	 * @param int direction
	 * @return Leaps\Image\Adapter
	 */
	public function flip($direction)
	{
		if ($direction != \Leaps\Image::HORIZONTAL && $direction != \Leaps\Image::VERTICAL) {
			$direction = \Leaps\Image::HORIZONTAL;
		}
		$this->_flip ( $direction );
		return $this;
	}

	/**
	 * Sharpen the image by a given amount
	 *
	 * @param int amount
	 * @return Leaps\Image\Adapter
	 */
	public function sharpen($amount)
	{
		if ($amount > 100) {
			$amount = 100;
		} else {
			if ($amount < 1) {
				$amount = 1;
			}
		}

		$this->_sharpen ( $amount );
		return $this;
	}

	/**
	 * Add a reflection to an image
	 *
	 * @param int height
	 * @param int opacity
	 * @param boolean fade_in
	 * @return Leaps\Image\Adapter
	 */
	public function reflection($height, $opacity = 100, $fade_in = false)
	{
		if ($height <= 0 || $height > $this->_height) {
			$height = ( int ) $this->_height;
		}
		if ($opacity < 0) {
			$opacity = 0;
		} else {
			if ($opacity > 100) {
				$opacity = 100;
			}
		}
		$this->_reflection ( $height, $opacity, $fade_in );
		return $this;
	}

	/**
	 * Add a watermark to an image with a specified opacity
	 *
	 * @param Leaps\Image\Adapter watermark
	 * @param int offset_x
	 * @param int offset_y
	 * @param int opacity
	 * @return Leaps\Image\Adapter
	 */
	public function watermark($watermark, $offset_x = 0, $offset_y = 0, $opacity = 100)
	{
		$tmp = $this->_width - $watermark->getWidth ();
		if ($offset_x < 0) {
			$offset_x = 0;
		} else {
			if ($offset_x > $tmp) {
				$offset_x = $tmp;
			}
		}
		$tmp = $this->_height - $watermark->getHeight ();
		if ($offset_y < 0) {
			$offset_y = 0;
		} else {
			if ($offset_y > $tmp) {
				$offset_y = $tmp;
			}
		}
		if ($opacity < 0) {
			$opacity = 0;
		} else {
			if ($opacity > 100) {
				$opacity = 100;
			}
		}
		$this->_watermark ( $watermark, $offset_x, $offset_y, $opacity );
		return $this;
	}

	/**
	 * Add a text to an image with a specified opacity
	 *
	 * @param string text
	 * @param int offset_x
	 * @param int offset_y
	 * @param int opacity
	 * @param string color
	 * @param int size
	 * @param string fontfile
	 * @return Leaps\Image\Adapter
	 */
	public function text($text, $offset_x = 0, $offset_y = 0, $opacity = 100, $color = "000000", $size = 12, $fontfile = null)
	{
		if ($opacity < 0) {
			$opacity = 0;
		} else {
			if ($opacity > 100) {
				$opacity = 100;
			}
		}
		if (strlen ( $color ) > 1 && substr ( $color, 0, 1 ) === "#") {
			$color = substr ( $color, 1 );
		}
		if (strlen ( $color ) == 3) {
			$color = preg_replace ( "/./", "$0$0", $color );
		}
		$colors = array_map ( "hexdec", str_split ( $color, 2 ) );
		$this->_text ( $text, $offset_x, $offset_y, $opacity, $colors [0], $colors [1], $colors [2], $size, $fontfile );
		return $this;
	}

	/**
	 * Composite one image onto another
	 *
	 * @param Leaps\Image\Adapter watermark
	 * @return Leaps\Image\Adapter
	 */
	public function mask($watermark)
	{
		$this->_mask ( $watermark );
		return $this;
	}

	/**
	 * Set the background color of an image
	 *
	 * @param string color
	 * @param int opacity
	 * @return Leaps\Image\Adapter
	 */
	public function background($color, $opacity = 100)
	{
		if (strlen ( $color ) > 1 && substr ( $color, 0, 1 ) === "#") {
			$color = substr ( $color, 1 );
		}
		if (strlen ( $color ) == 3) {
			$color = preg_replace ( "/./", "$0$0", $color );
		}
		$colors = array_map ( "hexdec", str_split ( $color, 2 ) );
		$this->_background ( $colors [0], $colors [1], $colors [2], $opacity );
		return $this;
	}

	/**
	 * Blur image
	 *
	 * @param int radius
	 * @return Leaps\Image\Adapter
	 */
	public function blur($radius)
	{
		if ($radius < 1) {
			$radius = 1;
		} else {
			if ($radius > 100) {
				$radius = 100;
			}
		}

		$this->_blur ( $radius );
		return $this;
	}

	/**
	 * Pixelate image
	 *
	 * @param int amount
	 * @return Leaps\Image\Adapter
	 */
	public function pixelate($amount)
	{
		if ($amount < 2) {
			$amount = 2;
		}
		$this->_pixelate ( $amount );
		return $this;
	}

	/**
	 * Save the image
	 *
	 * @param string file
	 * @param int quality
	 * @return Leaps\Image\Adapter
	 */
	public function save($file = null, $quality = 100)
	{
		if (! $file) {
			$file = ( string ) $this->_realpath;
		}

		if ($quality < 1) {
			$quality = 1;
		} else {
			if ($quality > 100) {
				$quality = 100;
			}
		}

		$this->_save ( $file, $quality );
		return $this;
	}

	/**
	 * Render the image and return the binary string
	 *
	 * @param string ext
	 * @param int quality
	 * @return string
	 */
	public function render($ext = null, $quality = 100)
	{
		if (! $ext) {
			$ext = ( string ) pathinfo ( $this->_file, PATHINFO_EXTENSION );
		}

		if (empty ( $ext )) {
			$ext = "png";
		}

		if ($quality < 1) {
			$quality = 1;
		} else {
			if ($quality > 100) {
				$quality = 100;
			}
		}
		return $this->_render ( $ext, $quality );
	}
	public function getImage()
	{
		return $this->_image;
	}
	public function getRealpath()
	{
		return $this->_realpath;
	}
	public function getWidth()
	{
		return $this->_width;
	}
	public function getType()
	{
		return $this->_type;
	}
	public function getHeight()
	{
		return $this->_height;
	}
	public function getMime()
	{
		return $this->_mime;
	}
}