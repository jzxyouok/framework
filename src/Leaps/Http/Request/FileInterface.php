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
namespace Leaps\Http\Request;

/**
 * Leaps\Http\Request\FileInterface
 *
 * Interface for Leaps\Http\Request\File
 *
 */
interface FileInterface
{

	/**
	 * Leaps\Http\Request\FileInterface constructor
	 *
	 * @param array file
	 */
	public function __construct($file, $key = null);

	/**
	 * Returns the file size of the uploaded file
	 *
	 * @return int
	*/
	public function getSize();

	/**
	 * Returns the real name of the uploaded file
	 *
	 * @return string
	*/
	public function getName();

	/**
	 * Returns the temporal name of the uploaded file
	 *
	 * @return string
	*/
	public function getTempName();

	/**
	 * Returns the mime type reported by the browser
	 * This mime type is not completely secure, use getRealType() instead
	 *
	 * @return string
	*/
	public function getType();

	/**
	 * Gets the real mime type of the upload file using finfo
	 *
	 * @return string
	*/
	public function getRealType();

	/**
	 * Move the temporary file to a destination
	 *
	 * @param string destination
	 * @return boolean
	*/
	public function moveTo($destination);

}