<?php
namespace Think;
class Upload
{
	private $config = array('mimes' => array(), 'maxSize' => 0, 'exts' => array(), 'autoSub' => true, 'subName' => array('date', 'Y-m-d'), 'rootPath' => './Uploads/', 'savePath' => '', 'saveName' => array('uniqid', ''), 'saveExt' => '', 'replace' => false, 'hash' => true, 'callback' => false, 'driver' => '', 'driverConfig' => array(),);
	private $error = '';
	private $uploader;

	public function __construct($config = array(), $driver = '', $driverConfig = null)
	{
		$this->config = array_merge($this->config, $config);
		$this->setDriver($driver, $driverConfig);
		if (!empty($this->config['mimes'])) {
			if (is_string($this->mimes)) {
				$this->config['mimes'] = explode(',', $this->mimes);
			}
			$this->config['mimes'] = array_map('strtolower', $this->mimes);
		}
		if (!empty($this->config['exts'])) {
			if (is_string($this->exts)) {
				$this->config['exts'] = explode(',', $this->exts);
			}
			$this->config['exts'] = array_map('strtolower', $this->exts);
		}
	}

	public function __get($name)
	{
		return $this->config[$name];
	}

	public function __set($name, $value)
	{
		if (isset($this->config[$name])) {
			$this->config[$name] = $value;
			if ($name == 'driverConfig') {
				$this->setDriver();
			}
		}
	}

	public function __isset($name)
	{
		return isset($this->config[$name]);
	}

	public function getError()
	{
		return $this->error;
	}

	public function uploadOne($file)
	{
		$info = $this->upload(array($file));
		return $info ? $info[0] : $info;
	}

	public function upload($files = '')
	{
		if ('' === $files) {
			$files = $_FILES;
		}
		if (empty($files)) {
			$this->error = '没有上传的文件！';
			return false;
		}
		if (!$this->uploader->checkRootPath($this->rootPath)) {
			$this->error = $this->uploader->getError();
			return false;
		}
		if (!$this->uploader->checkSavePath($this->savePath)) {
			$this->error = $this->uploader->getError();
			return false;
		}
		$info = array();
		if (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
		}
		$files = $this->dealFiles($files);
		foreach ($files as $key => $file) {
			$file['name'] = strip_tags($file['name']);
			if (!isset($file['key'])) $file['key'] = $key;
			if (isset($finfo)) {
				$file['type'] = finfo_file($finfo, $file['tmp_name']);
			}
			$file['ext'] = pathinfo($file['name'], PATHINFO_EXTENSION);
			if (!$this->check($file)) {
				continue;
			}
			if ($this->hash) {
				$file['md5'] = md5_file($file['tmp_name']);
				$file['sha1'] = sha1_file($file['tmp_name']);
			}
			$data = call_user_func($this->callback, $file);
			if ($this->callback && $data) {
				if (file_exists('.' . $data['path'])) {
					$info[$key] = $data;
					continue;
				} elseif ($this->removeTrash) {
					call_user_func($this->removeTrash, $data);
				}
			}
			$savename = $this->getSaveName($file);
			if (false == $savename) {
				continue;
			} else {
				$file['savename'] = $savename;
			}
			$subpath = $this->getSubPath($file['name']);
			if (false === $subpath) {
				continue;
			} else {
				$file['savepath'] = $this->savePath . $subpath;
			}
			$ext = strtolower($file['ext']);
			if (in_array($ext, array('gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf'))) {
				$imginfo = getimagesize($file['tmp_name']);
				if (empty($imginfo)) {
					$this->error = '非法图像文件！';
					continue;
				}
			}
			if ($this->uploader->save($file, $this->replace)) {
				unset($file['error'], $file['tmp_name']);
				$info[$key] = $file;
			} else {
				$this->error = $this->uploader->getError();
			}
		}
		if (isset($finfo)) {
			finfo_close($finfo);
		}
		return empty($info) ? false : $info;
	}

	public function getUploader()
	{
		return $this->uploader;
	}

	private function dealFiles($files)
	{
		$fileArray = array();
		$n = 0;
		foreach ($files as $key => $file) {
			if (is_array($file['name'])) {
				$keys = array_keys($file);
				$count = count($file['name']);
				for ($i = 0; $i < $count; $i++) {
					$fileArray[$n]['key'] = $key;
					foreach ($keys as $_key) {
						$fileArray[$n][$_key] = $file[$_key][$i];
					}
					$n++;
				}
			} else {
				$fileArray = $files;
				break;
			}
		}
		return $fileArray;
	}

	private function setDriver($driver = null, $config = null)
	{
		$driver = $driver ?: ($this->driver ?: C('FILE_UPLOAD_TYPE'));
		$config = $config ?: ($this->driverConfig ?: C('UPLOAD_TYPE_CONFIG'));
		$class = strpos($driver, '\\') ? $driver : 'Think\\Upload\\Driver\\' . ucfirst(strtolower($driver));
		$this->uploader = new $class($config);
		if (!$this->uploader) {
			E("不存在上传驱动：{$name}");
		}
	}

	private function check($file)
	{
		if ($file['error']) {
			$this->error($file['error']);
			return false;
		}
		if (empty($file['name'])) {
			$this->error = '未知上传错误！';
		}
		if (!is_uploaded_file($file['tmp_name'])) {
			$this->error = '非法上传文件！';
			return false;
		}
		if (!$this->checkSize($file['size'])) {
			$this->error = '上传文件大小不符！';
			return false;
		}
		if (!$this->checkMime($file['type'])) {
			$this->error = '上传文件MIME类型不允许！';
			return false;
		}
		if (!$this->checkExt($file['ext'])) {
			$this->error = '上传文件后缀不允许';
			return false;
		}
		return true;
	}

	private function error($errorNo)
	{
		switch ($errorNo) {
			case 1:
				$this->error = '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值！';
				break;
			case 2:
				$this->error = '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值！';
				break;
			case 3:
				$this->error = '文件只有部分被上传！';
				break;
			case 4:
				$this->error = '没有文件被上传！';
				break;
			case 6:
				$this->error = '找不到临时文件夹！';
				break;
			case 7:
				$this->error = '文件写入失败！';
				break;
			default:
				$this->error = '未知上传错误！';
		}
	}

	private function checkSize($size)
	{
		return !($size > $this->maxSize) || (0 == $this->maxSize);
	}

	private function checkMime($mime)
	{
		return empty($this->config['mimes']) ? true : in_array(strtolower($mime), $this->mimes);
	}

	private function checkExt($ext)
	{
		return empty($this->config['exts']) ? true : in_array(strtolower($ext), $this->exts);
	}

	private function getSaveName($file)
	{
		$rule = $this->saveName;
		if (empty($rule)) {
			$filename = substr(pathinfo("_{$file['name']}", PATHINFO_FILENAME), 1);
			$savename = $filename;
		} else {
			$savename = $this->getName($rule, $file['name']);
			if (empty($savename)) {
				$this->error = '文件命名规则错误！';
				return false;
			}
		}
		$ext = empty($this->config['saveExt']) ? $file['ext'] : $this->saveExt;
		return $savename . '.' . $ext;
	}

	private function getSubPath($filename)
	{
		$subpath = '';
		$rule = $this->subName;
		if ($this->autoSub && !empty($rule)) {
			$subpath = $this->getName($rule, $filename) . '/';
			if (!empty($subpath) && !$this->uploader->mkdir($this->savePath . $subpath)) {
				$this->error = $this->uploader->getError();
				return false;
			}
		}
		return $subpath;
	}

	private function getName($rule, $filename)
	{
		$name = '';
		if (is_array($rule)) {
			$func = $rule[0];
			$param = (array)$rule[1];
			foreach ($param as &$value) {
				$value = str_replace('__FILE__', $filename, $value);
			}
			$name = call_user_func_array($func, $param);
		} elseif (is_string($rule)) {
			if (function_exists($rule)) {
				$name = call_user_func($rule);
			} else {
				$name = $rule;
			}
		}
		return $name;
	}
} 