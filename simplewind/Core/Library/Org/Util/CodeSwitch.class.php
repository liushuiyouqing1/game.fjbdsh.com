<?php
namespace Org\Util;
class CodeSwitch
{
	static private $error = array();
	static private $info = array();

	static private function error($msg)
	{
		self::$error[] = $msg;
	}

	static private function info($info)
	{
		self::$info[] = $info;
	}

	static function DetectAndSwitch($filename, $out_charset)
	{
		$fpr = fopen($filename, "r");
		$char1 = fread($fpr, 1);
		$char2 = fread($fpr, 1);
		$char3 = fread($fpr, 1);
		$originEncoding = "";
		if ($char1 == chr(239) && $char2 == chr(187) && $char3 == chr(191)) $originEncoding = "UTF-8 WITH BOM"; elseif ($char1 == chr(255) && $char2 == chr(254)) {
			self::error("不支持从UNICODE LE转换到UTF-8或GB编码");
			fclose($fpr);
			return;
		} elseif ($char1 == chr(254) && $char2 == chr(255)) {
			self::error("不支持从UNICODE BE转换到UTF-8或GB编码");
			fclose($fpr);
			return;
		} else {
			if (rewind($fpr) === false) {
				self::error($filename . "文件指针后移失败");
				fclose($fpr);
				return;
			}
			while (!feof($fpr)) {
				$char = fread($fpr, 1);
				if (ord($char) < 128) continue;
				if ((ord($char) & 224) == 224) {
					$char = fread($fpr, 1);
					if ((ord($char) & 128) == 128) {
						$char = fread($fpr, 1);
						if ((ord($char) & 128) == 128) {
							$originEncoding = "UTF-8";
							break;
						}
					}
				}
				if ((ord($char) & 192) == 192) {
					$char = fread($fpr, 1);
					if ((ord($char) & 128) == 128) {
						$originEncoding = "GB2312";
						break;
					}
				}
			}
		}
		if (strtoupper($out_charset) == $originEncoding) {
			self::info("文件" . $filename . "转码检查完成,原始文件编码" . $originEncoding);
			fclose($fpr);
		} else {
			$originContent = "";
			if ($originEncoding == "UTF-8 WITH BOM") {
				fseek($fpr, 3);
				$originContent = fread($fpr, filesize($filename) - 3);
				fclose($fpr);
			} elseif (rewind($fpr) != false) {
				$originContent = fread($fpr, filesize($filename));
				fclose($fpr);
			} else {
				self::error("文件编码不正确或指针后移失败");
				fclose($fpr);
				return;
			}
			$content = iconv(str_replace(" WITH BOM", "", $originEncoding), strtoupper($out_charset), $originContent);
			$fpw = fopen($filename, "w");
			fwrite($fpw, $content);
			fclose($fpw);
			if ($originEncoding != "") self::info("对文件" . $filename . "转码完成,原始文件编码" . $originEncoding . ",转换后文件编码" . strtoupper($out_charset)); elseif ($originEncoding == "") self::info("文件" . $filename . "中没有出现中文,但是可以断定不是带BOM的UTF-8编码,没有进行编码转换,不影响使用");
		}
	}

	static function searchdir($path, $mode = "FULL", $file_types = array(".html", ".php"), $maxdepth = -1, $d = 0)
	{
		if (substr($path, strlen($path) - 1) != '/') $path .= '/';
		$dirlist = array();
		if ($mode != "FILES") $dirlist[] = $path;
		if ($handle = @opendir($path)) {
			while (false !== ($file = readdir($handle))) {
				if ($file != '.' && $file != '..') {
					$file = $path . $file;
					if (!is_dir($file)) {
						if ($mode != "DIRS") {
							$extension = "";
							$extpos = strrpos($file, '.');
							if ($extpos !== false) $extension = substr($file, $extpos, strlen($file) - $extpos);
							$extension = strtolower($extension);
							if (in_array($extension, $file_types)) $dirlist[] = $file;
						}
					} elseif ($d >= 0 && ($d < $maxdepth || $maxdepth < 0)) {
						$result = self::searchdir($file . '/', $mode, $file_types, $maxdepth, $d + 1);
						$dirlist = array_merge($dirlist, $result);
					}
				}
			}
			closedir($handle);
		}
		if ($d == 0) natcasesort($dirlist);
		return ($dirlist);
	}

	static function CodingSwitch($app = "./", $charset = 'UTF-8', $mode = "FILES", $file_types = array(".html", ".php"))
	{
		self::info("注意: 程序使用的文件编码检测算法可能对某些特殊字符不适用");
		$filearr = self::searchdir($app, $mode, $file_types);
		foreach ($filearr as $file) self::DetectAndSwitch($file, $charset);
	}

	static public function getError()
	{
		return self::$error;
	}

	static public function getInfo()
	{
		return self::$info;
	}
}