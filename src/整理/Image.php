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
namespace Leaps;

class Image
{
	/*
	 * Resizing constraints
	 */
	const NONE = 1;
	const WIDTH = 2;
	const HEIGHT = 3;
	const AUTO = 4;
	const INVERSE = 5;
	const PRECISE = 6;
	const TENSILE = 7;

	// Flipping directions
	const HORIZONTAL = 11;
	const VERTICAL = 12;
}