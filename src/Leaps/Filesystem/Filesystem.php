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
namespace Leaps\Filesystem;

use Leaps\Core\Base;
use FilesystemIterator;

class Filesystem extends Base
{
	/**
	 * 判断文件是否存在
	 *
	 * @param string $path
	 * @return bool
	 */
	public function exists($path)
	{
		return file_exists ( $path );
	}

	/**
	 * 获取文件内容
	 *
	 * @param string $path
	 * @return string
	 */
	public function get($path)
	{
		if ($this->isFile ( $path )) {
			return file_get_contents ( $path );
		}
		throw new Exception ( "File does not exist at path {path}" );
	}

	/**
	 * 获取远程文件的内容
	 *
	 * @param string $path
	 * @return string
	 */
	public function getRemote($path)
	{
		return file_get_contents ( $path );
	}

	/**
	 * 有返回值的文件
	 *
	 * @param string $path
	 * @return mixed
	 */
	public function getRequire($path)
	{
		if ($this->isFile ( $path )) {
			return require $path;
		}
		throw new Exception ( "File does not exist at path {path}" );
	}

	/**
	 * 加载指定的文件
	 *
	 * @param string $file
	 * @return void
	 */
	public function requireOnce($file)
	{
		if ($this->isFile ( $file )) {
			require $file;
		}
	}

	/**
	 * 向文件写入内容
	 *
	 * @param string $path 文件绝对路径
	 * @param string $contents 内容
	 * @return int
	 */
	public function put($path, $contents)
	{
		return file_put_contents ( $path, $contents );
	}

	/**
	 * 追加内容到文件
	 *
	 * @param string $path 文件绝对路径
	 * @param string $data 内容
	 * @return int
	 */
	public function append($path, $data)
	{
		return file_put_contents ( $path, $data, FILE_APPEND );
	}

	/**
	 * 删除文件
	 *
	 * @param string $path 文件绝对路径
	 * @return bool
	 */
	public function delete($path)
	{
		if ($this->isFile ( $path )) {
			return @unlink ( $path );
		}
	}

	/**
	 * 将一个文件移动到新的位置
	 *
	 * @param string $path
	 * @param string $target
	 * @return void
	 */
	public function move($path, $target)
	{
		return rename ( $path, $target );
	}

	/**
	 * 复制文件到新路径
	 *
	 * @param string $path
	 * @param string $target
	 * @return void
	 */
	public function copy($path, $target)
	{
		return copy ( $path, $target );
	}

	/**
	 * 从路径获取一个文件的扩展名
	 *
	 * @param string $path
	 * @return string
	 */
	public function extension($path)
	{
		return pathinfo ( $path, PATHINFO_EXTENSION );
	}

	/**
	 * 返回文件类型
	 *
	 * @param string $path
	 * @return string
	 */
	public function type($path)
	{
		return filetype ( $path );
	}

	/**
	 * 获取文件大小
	 *
	 * @param string $path
	 * @return int
	 */
	public function size($path)
	{
		return filesize ( $path );
	}

	/**
	 * 修改文件时间
	 */
	public function touch($path, $time, $atime = null)
	{
		return touch ( $path, $time, $atime );
	}

	/**
	 * 获取文件的最后修改时间
	 *
	 * @param string $path
	 * @return int
	 */
	public function lastModified($fileName)
	{
		return filemtime ( $fileName );
	}

	/**
	 * 确定给定的路径是否是一个目录
	 *
	 * @param string $directory
	 * @return bool
	 */
	public function isDirectory($directory)
	{
		return is_dir ( $directory );
	}

	/**
	 * 判断路径是否可写
	 *
	 * @param string $path
	 * @return bool
	 */
	public function isWritable($path)
	{
		return is_writable ( $path );
	}

	/**
	 * 判断是否是文件
	 *
	 * @param string $file
	 * @return bool
	 */
	public function isFile($file)
	{
		return is_file ( $file );
	}

	/**
	 * 找到一个给定的模式匹配的路径名称。
	 *
	 * @param string $pattern 必需。规定检索模式。
	 * @param int $flags 可选。规定特殊的设定。
	 * @return array
	 */
	public function glob($pattern, $flags = 0)
	{
		return glob ( $pattern, $flags );
	}

	/**
	 * 得到一个阵列中的一个目录下的所有文件。
	 *
	 * @param string $directory
	 * @return array
	 */
	public function files($directory)
	{
		$glob = glob ( $directory . "/* " );
		if ($glob === false) {
			return [ ];
		}
		return array_filter ( $glob, function ($file)
		{
			return filetype ( $file ) == "file";
		} );
	}

	/**
	 * 创建一个文件夹
	 *
	 * @param string $path
	 * @param int $mode
	 * @param bool $recursive
	 * @return bool
	 */
	public function createDirectory($path, $mode = 0777, $recursive = false)
	{
		if (is_dir ( $path )) {
			return true;
		}
		$parentDir = dirname ( $path );
		if ($recursive && ! is_dir ( $parentDir )) {
			$this->createDirectory ( $parentDir, $mode, true );
		}
		$result = @mkdir ( $path, $mode );
		chmod ( $path, $mode );
		return $result;
	}

	/**
	 * 从一个位置到另一个复制目录。
	 *
	 * @param string $directory
	 * @param string $destination
	 * @param int $options
	 * @return void
	 */
	public function copyDirectory($directory, $destination, $options = null)
	{
		if (! is_dir ( $directory )) {
			return false;
		}
		$options = $options ? $options : FilesystemIterator::SKIP_DOTS;
		if (! is_dir ( $destination )) {
			$this->copyDirectory ( $destination, 0777, true );
		}
		$items = new FilesystemIterator ( $directory, $options );
		foreach ( $items as $item ) {
			$target = $destination . "/" . $item->getBasename ();
			if ($item->isDir ()) {
				$path = $item->getPathname ();
				if (! $this->copyDirectory ( $path, $target, $options )) {
					return false;
				}
			} else {
				if (! $this->copy ( $item->getPathname (), $target )) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * 递归删除一个目录。 The directory itself may be optionally preserved.
	 *
	 * @param string $directory
	 * @param bool $preserve
	 * @return void
	 */
	public function deleteDirectory($directory, $preserve = false)
	{
		if (! is_dir ( $directory )) {
			return true;
		}
		if (! is_link ( $directory )) {
			$items = new FilesystemIterator ( $directory );
			foreach ( $items as $item ) {
				if ($item->isDir ()) {
					$this->deleteDirectory ( $item->getPathname () );
				} else {
					$this->delete ( $item->getPathname () );
				}
			}
			if (! $preserve) {
				@rmdir ( $directory );
			}
		} else {
			unlink ( $directory );
		}
	}

	/**
	 * 清空指定目录下的所有文件和文件夹。
	 *
	 * @param string $directory
	 * @return void
	 */
	public function cleanDirectory($directory)
	{
		return $this->deleteDirectory ( $directory, true );
	}

	/**
	 * Determines the MIME type based on the extension name of the specified file.
	 * This method will use a local map between extension names and MIME types.
	 *
	 * @param string $fileName the file name.
	 * @return string the MIME type. Null is returned if the MIME type cannot be determined.
	 */
	public function getMimeType($fileName)
	{
		return MimeType::getMimeType ( $fileName );
	}
}