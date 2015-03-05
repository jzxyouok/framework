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

use Leaps\Image;

interface AdapterInterface
{
	public function resize($width = null, $height = null, $master = Image::AUTO);
	public function crop($width, $height, $offset_x = null, $offset_y = null);
	public function rotate($degrees);
	public function flip($direction);
	public function sharpen($amount);
	public function reflection($height, $opacity = 100, $fade_in = false);
	public function watermark($watermark, $offset_x = 0, $offset_y = 0, $opacity = 100);
	public function text($text, $offset_x = 0, $offset_y = 0, $opacity = 100, $color = "000000", $size = 12, $fontfile = null);
	public function mask($watermark);
	public function background($color, $opacity = 100);
	public function blur($radius);
	public function pixelate($amount);
	public function save($file = null, $quality = 100);
	public function render($ext = null, $quality = 100);
}