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

use Leaps\Http\Request\FileInterface;

/**
 * Leaps\Http\Request\File
 *
 * Provides OO wrappers to the $_FILES superglobal
 *
 *<code>
 *	class PostsController extends \Leaps\Mvc\Controller
 *	{
 *
 *		public function uploadAction()
 *		{
 *			//Check if the user has uploaded files
 *			if ($this->request->hasFiles() == true) {
 *				//Print the real file names and their sizes
 *				foreach ($this->request->getUploadedFiles() as $file){
 *					echo $file->getName(), " ", $file->getSize(), "\n";
 *				}
 *			}
 *		}
 *
 *	}
 *</code>
 */
class File implements FileInterface
{

	protected $_name;

	protected $_tmp;

	protected $_size;

	protected $_type;

	protected $_realType;

	protected $_error;

	protected $_key;

	protected $_extension;


	/**
	 * Leaps\Http\Request\File constructor
	 *
	 * @param array file
	 */
	public function __construct($file, $key = null)
	{
		if (isset($file["name"])) {
			$this->_name = $file["name"];
			if (defined("PATHINFO_EXTENSION")) {
				$this->_extension = pathinfo($file["name"], PATHINFO_EXTENSION);
			}
		}

		if (isset($file["tmp_name"])) {
			$this->_tmp = $file["tmp_name"];
		}

		if (isset($file["size"])) {
			$this->_size =$file["size"];
		}

		if (isset($file["type"])) {
			$this->_type = $file["type"];
		}

		if  (isset($file["error"])) {
			$this->_error = $file["error"];
		}

		if ($key) {
			$this->_key = $key;
		}
	}

	/**
	 * Returns the file size of the uploaded file
	 *
	 * @return int
	 */
	public function getSize()
	{
		return $this->_size;
	}

	/**
	 * Returns the real name of the uploaded file
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * Returns the temporal name of the uploaded file
	 *
	 * @return string
	 */
	public function getTempName()
	{
		return $this->_tmp;
	}

	/**
	 * Returns the mime type reported by the browser
	 * This mime type is not completely secure, use getRealType() instead
	 *
	 * @return string
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * Gets the real mime type of the upload file using finfo
	 *
	 * @return string
	 */
	public function getRealType()
	{
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		if (!is_resource($finfo)) {
			return "";
		}
		$mime = finfo_file($finfo, $this->_tmp);
		finfo_close($finfo);
		return $mime;
	}

	/**
	 * 检测文件是否通过POST上传
	 *
	 * @return boolean
	 */
	public function isUploadedFile()
	{
		$tmp = $this->getTempName();
		return is_string($tmp) && is_uploaded_file($tmp);
	}

	/**
	 * 获取错误信息
	 */
	public function getError(){
		return $this->_error;
	}

	public function getKey(){
		return $this->_key;
	}

	public function getExtension(){
		return $this->_extension;
	}

	/**
	 * Moves the temporary file to a destination within the application
	 *
	 * @param string destination
	 * @return boolean
	 */
	public function moveTo($destination)
	{
		return move_uploaded_file($this->_tmp, $destination);
	}
}
