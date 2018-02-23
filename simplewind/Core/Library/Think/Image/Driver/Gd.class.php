<?php
namespace Think\Image\Driver;

use Think\Image;

class Gd
{
	private $img;
	private $info;

	public function __construct($imgname = null)
	{
		$imgname && $this->open($imgname);
	}

	public function open($imgname)
	{
		if (!is_file($imgname)) E('不存在的图像文件');
		$info = getimagesize($imgname);
		if (false === $info || (IMAGETYPE_GIF === $info[2] && empty($info['bits']))) {
			E('非法图像文件');
		}
		$this->info = array('width' => $info[0], 'height' => $info[1], 'type' => image_type_to_extension($info[2], false), 'mime' => $info['mime'],);
		empty($this->img) || imagedestroy($this->img);
		if ('gif' == $this->info['type']) {
			$class = 'Think\\Image\\Driver\\GIF';
			$this->gif = new $class($imgname);
			$this->img = imagecreatefromstring($this->gif->image());
		} else {
			$fun = "imagecreatefrom{$this->info['type']}";
			$this->img = $fun($imgname);
		}
	}

	public function save($imgname, $type = null, $quality = 80, $interlace = true)
	{
		if (empty($this->img)) E('没有可以被保存的图像资源');
		if (is_null($type)) {
			$type = $this->info['type'];
		} else {
			$type = strtolower($type);
		}
		if ('jpeg' == $type || 'jpg' == $type) {
			imageinterlace($this->img, $interlace);
			imagejpeg($this->img, $imgname, $quality);
		} elseif ('gif' == $type && !empty($this->gif)) {
			$this->gif->save($imgname);
		} else {
			$fun = 'image' . $type;
			$fun($this->img, $imgname);
		}
	}

	public function width()
	{
		if (empty($this->img)) E('没有指定图像资源');
		return $this->info['width'];
	}

	public function height()
	{
		if (empty($this->img)) E('没有指定图像资源');
		return $this->info['height'];
	}

	public function type()
	{
		if (empty($this->img)) E('没有指定图像资源');
		return $this->info['type'];
	}

	public function mime()
	{
		if (empty($this->img)) E('没有指定图像资源');
		return $this->info['mime'];
	}

	public function size()
	{
		if (empty($this->img)) E('没有指定图像资源');
		return array($this->info['width'], $this->info['height']);
	}

	public function crop($w, $h, $x = 0, $y = 0, $width = null, $height = null)
	{
		if (empty($this->img)) E('没有可以被裁剪的图像资源');
		empty($width) && $width = $w;
		empty($height) && $height = $h;
		do {
			$img = imagecreatetruecolor($width, $height);
			$color = imagecolorallocate($img, 255, 255, 255);
			imagefill($img, 0, 0, $color);
			imagecopyresampled($img, $this->img, 0, 0, $x, $y, $width, $height, $w, $h);
			imagedestroy($this->img);
			$this->img = $img;
		} while (!empty($this->gif) && $this->gifNext());
		$this->info['width'] = $width;
		$this->info['height'] = $height;
	}

	public function thumb($width, $height, $type = Image::IMAGE_THUMB_SCALE)
	{
		if (empty($this->img)) E('没有可以被缩略的图像资源');
		$w = $this->info['width'];
		$h = $this->info['height'];
		switch ($type) {
			case Image::IMAGE_THUMB_SCALE:
				if ($w < $width && $h < $height) return;
				$scale = min($width / $w, $height / $h);
				$x = $y = 0;
				$width = $w * $scale;
				$height = $h * $scale;
				break;
			case Image::IMAGE_THUMB_CENTER:
				$scale = max($width / $w, $height / $h);
				$w = $width / $scale;
				$h = $height / $scale;
				$x = ($this->info['width'] - $w) / 2;
				$y = ($this->info['height'] - $h) / 2;
				break;
			case Image::IMAGE_THUMB_NORTHWEST:
				$scale = max($width / $w, $height / $h);
				$x = $y = 0;
				$w = $width / $scale;
				$h = $height / $scale;
				break;
			case Image::IMAGE_THUMB_SOUTHEAST:
				$scale = max($width / $w, $height / $h);
				$w = $width / $scale;
				$h = $height / $scale;
				$x = $this->info['width'] - $w;
				$y = $this->info['height'] - $h;
				break;
			case Image::IMAGE_THUMB_FILLED:
				if ($w < $width && $h < $height) {
					$scale = 1;
				} else {
					$scale = min($width / $w, $height / $h);
				}
				$neww = $w * $scale;
				$newh = $h * $scale;
				$posx = ($width - $w * $scale) / 2;
				$posy = ($height - $h * $scale) / 2;
				do {
					$img = imagecreatetruecolor($width, $height);
					$color = imagecolorallocate($img, 255, 255, 255);
					imagefill($img, 0, 0, $color);
					imagecopyresampled($img, $this->img, $posx, $posy, $x, $y, $neww, $newh, $w, $h);
					imagedestroy($this->img);
					$this->img = $img;
				} while (!empty($this->gif) && $this->gifNext());
				$this->info['width'] = $width;
				$this->info['height'] = $height;
				return;
			case Image::IMAGE_THUMB_FIXED:
				$x = $y = 0;
				break;
			default:
				E('不支持的缩略图裁剪类型');
		}
		$this->crop($w, $h, $x, $y, $width, $height);
	}

	public function water($source, $locate = Image::IMAGE_WATER_SOUTHEAST, $alpha = 80)
	{
		if (empty($this->img)) E('没有可以被添加水印的图像资源');
		if (!is_file($source)) E('水印图像不存在');
		$info = getimagesize($source);
		if (false === $info || (IMAGETYPE_GIF === $info[2] && empty($info['bits']))) {
			E('非法水印文件');
		}
		$fun = 'imagecreatefrom' . image_type_to_extension($info[2], false);
		$water = $fun($source);
		imagealphablending($water, true);
		switch ($locate) {
			case Image::IMAGE_WATER_SOUTHEAST:
				$x = $this->info['width'] - $info[0];
				$y = $this->info['height'] - $info[1];
				break;
			case Image::IMAGE_WATER_SOUTHWEST:
				$x = 0;
				$y = $this->info['height'] - $info[1];
				break;
			case Image::IMAGE_WATER_NORTHWEST:
				$x = $y = 0;
				break;
			case Image::IMAGE_WATER_NORTHEAST:
				$x = $this->info['width'] - $info[0];
				$y = 0;
				break;
			case Image::IMAGE_WATER_CENTER:
				$x = ($this->info['width'] - $info[0]) / 2;
				$y = ($this->info['height'] - $info[1]) / 2;
				break;
			case Image::IMAGE_WATER_SOUTH:
				$x = ($this->info['width'] - $info[0]) / 2;
				$y = $this->info['height'] - $info[1];
				break;
			case Image::IMAGE_WATER_EAST:
				$x = $this->info['width'] - $info[0];
				$y = ($this->info['height'] - $info[1]) / 2;
				break;
			case Image::IMAGE_WATER_NORTH:
				$x = ($this->info['width'] - $info[0]) / 2;
				$y = 0;
				break;
			case Image::IMAGE_WATER_WEST:
				$x = 0;
				$y = ($this->info['height'] - $info[1]) / 2;
				break;
			default:
				if (is_array($locate)) {
					list($x, $y) = $locate;
				} else {
					E('不支持的水印位置类型');
				}
		}
		do {
			$src = imagecreatetruecolor($info[0], $info[1]);
			$color = imagecolorallocate($src, 255, 255, 255);
			imagefill($src, 0, 0, $color);
			imagecopy($src, $this->img, 0, 0, $x, $y, $info[0], $info[1]);
			imagecopy($src, $water, 0, 0, 0, 0, $info[0], $info[1]);
			imagecopymerge($this->img, $src, $x, $y, 0, 0, $info[0], $info[1], $alpha);
			imagedestroy($src);
		} while (!empty($this->gif) && $this->gifNext());
		imagedestroy($water);
	}

	public function text($text, $font, $size, $color = '#00000000', $locate = Image::IMAGE_WATER_SOUTHEAST, $offset = 0, $angle = 0)
	{
		if (empty($this->img)) E('没有可以被写入文字的图像资源');
		if (!is_file($font)) E("不存在的字体文件：{$font}");
		$info = imagettfbbox($size, $angle, $font, $text);
		$minx = min($info[0], $info[2], $info[4], $info[6]);
		$maxx = max($info[0], $info[2], $info[4], $info[6]);
		$miny = min($info[1], $info[3], $info[5], $info[7]);
		$maxy = max($info[1], $info[3], $info[5], $info[7]);
		$x = $minx;
		$y = abs($miny);
		$w = $maxx - $minx;
		$h = $maxy - $miny;
		switch ($locate) {
			case Image::IMAGE_WATER_SOUTHEAST:
				$x += $this->info['width'] - $w;
				$y += $this->info['height'] - $h;
				break;
			case Image::IMAGE_WATER_SOUTHWEST:
				$y += $this->info['height'] - $h;
				break;
			case Image::IMAGE_WATER_NORTHWEST:
				break;
			case Image::IMAGE_WATER_NORTHEAST:
				$x += $this->info['width'] - $w;
				break;
			case Image::IMAGE_WATER_CENTER:
				$x += ($this->info['width'] - $w) / 2;
				$y += ($this->info['height'] - $h) / 2;
				break;
			case Image::IMAGE_WATER_SOUTH:
				$x += ($this->info['width'] - $w) / 2;
				$y += $this->info['height'] - $h;
				break;
			case Image::IMAGE_WATER_EAST:
				$x += $this->info['width'] - $w;
				$y += ($this->info['height'] - $h) / 2;
				break;
			case Image::IMAGE_WATER_NORTH:
				$x += ($this->info['width'] - $w) / 2;
				break;
			case Image::IMAGE_WATER_WEST:
				$y += ($this->info['height'] - $h) / 2;
				break;
			default:
				if (is_array($locate)) {
					list($posx, $posy) = $locate;
					$x += $posx;
					$y += $posy;
				} else {
					E('不支持的文字位置类型');
				}
		}
		if (is_array($offset)) {
			$offset = array_map('intval', $offset);
			list($ox, $oy) = $offset;
		} else {
			$offset = intval($offset);
			$ox = $oy = $offset;
		}
		if (is_string($color) && 0 === strpos($color, '#')) {
			$color = str_split(substr($color, 1), 2);
			$color = array_map('hexdec', $color);
			if (empty($color[3]) || $color[3] > 127) {
				$color[3] = 0;
			}
		} elseif (!is_array($color)) {
			E('错误的颜色值');
		}
		do {
			$col = imagecolorallocatealpha($this->img, $color[0], $color[1], $color[2], $color[3]);
			imagettftext($this->img, $size, $angle, $x + $ox, $y + $oy, $col, $font, $text);
		} while (!empty($this->gif) && $this->gifNext());
	}

	private function gifNext()
	{
		ob_start();
		ob_implicit_flush(0);
		imagegif($this->img);
		$img = ob_get_clean();
		$this->gif->image($img);
		$next = $this->gif->nextImage();
		if ($next) {
			$this->img = imagecreatefromstring($next);
			return $next;
		} else {
			$this->img = imagecreatefromstring($this->gif->image());
			return false;
		}
	}

	public function __destruct()
	{
		empty($this->img) || imagedestroy($this->img);
	}
}