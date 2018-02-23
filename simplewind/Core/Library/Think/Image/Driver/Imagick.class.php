<?php
namespace Think\Image\Driver;

use Think\Image;

class Imagick
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
		empty($this->img) || $this->img->destroy();
		$this->img = new \Imagick(realpath($imgname));
		$this->info = array('width' => $this->img->getImageWidth(), 'height' => $this->img->getImageHeight(), 'type' => strtolower($this->img->getImageFormat()), 'mime' => $this->img->getImageMimeType(),);
	}

	public function save($imgname, $type = null, $quality = 80, $interlace = true)
	{
		if (empty($this->img)) E('没有可以被保存的图像资源');
		if (is_null($type)) {
			$type = $this->info['type'];
		} else {
			$type = strtolower($type);
			$this->img->setImageFormat($type);
		}
		if ('jpeg' == $type || 'jpg' == $type) {
			$this->img->setImageInterlaceScheme(1);
		}
		$this->img->setImageCompressionQuality($quality);
		$this->img->stripImage();
		$imgname = realpath(dirname($imgname)) . '/' . basename($imgname);
		if ('gif' == $type) {
			$this->img->writeImages($imgname, true);
		} else {
			$this->img->writeImage($imgname);
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
		if ('gif' == $this->info['type']) {
			$img = $this->img->coalesceImages();
			$this->img->destroy();
			do {
				$this->_crop($w, $h, $x, $y, $width, $height, $img);
			} while ($img->nextImage());
			$this->img = $img->deconstructImages();
			$img->destroy();
		} else {
			$this->_crop($w, $h, $x, $y, $width, $height);
		}
	}

	private function _crop($w, $h, $x, $y, $width, $height, $img = null)
	{
		is_null($img) && $img = $this->img;
		$info = $this->info;
		if ($x != 0 || $y != 0 || $w != $info['width'] || $h != $info['height']) {
			$img->cropImage($w, $h, $x, $y);
			$img->setImagePage($w, $h, 0, 0);
		}
		if ($w != $width || $h != $height) {
			$img->sampleImage($width, $height);
		}
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
				$newimg = new \Imagick();
				$newimg->newImage($width, $height, 'white', $this->info['type']);
				if ('gif' == $this->info['type']) {
					$imgs = $this->img->coalesceImages();
					$img = new \Imagick();
					$this->img->destroy();
					do {
						$image = $this->_fill($newimg, $posx, $posy, $neww, $newh, $imgs);
						$img->addImage($image);
						$img->setImageDelay($imgs->getImageDelay());
						$img->setImagePage($width, $height, 0, 0);
						$image->destroy();
					} while ($imgs->nextImage());
					$this->img->destroy();
					$this->img = $img->deconstructImages();
					$imgs->destroy();
					$img->destroy();
				} else {
					$img = $this->_fill($newimg, $posx, $posy, $neww, $newh);
					$this->img->destroy();
					$this->img = $img;
				}
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

	private function _fill($newimg, $posx, $posy, $neww, $newh, $img = null)
	{
		is_null($img) && $img = $this->img;
		$draw = new \ImagickDraw();
		$draw->composite($img->getImageCompose(), $posx, $posy, $neww, $newh, $img);
		$image = $newimg->clone();
		$image->drawImage($draw);
		$draw->destroy();
		return $image;
	}

	public function water($source, $locate = Image::IMAGE_WATER_SOUTHEAST, $alpha = 80)
	{
		if (empty($this->img)) E('没有可以被添加水印的图像资源');
		if (!is_file($source)) E('水印图像不存在');
		$water = new \Imagick(realpath($source));
		$info = array($water->getImageWidth(), $water->getImageHeight());
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
		$draw = new \ImagickDraw();
		$draw->composite($water->getImageCompose(), $x, $y, $info[0], $info[1], $water);
		if ('gif' == $this->info['type']) {
			$img = $this->img->coalesceImages();
			$this->img->destroy();
			do {
				$img->drawImage($draw);
			} while ($img->nextImage());
			$this->img = $img->deconstructImages();
			$img->destroy();
		} else {
			$this->img->drawImage($draw);
		}
		$draw->destroy();
		$water->destroy();
	}

	public function text($text, $font, $size, $color = '#00000000', $locate = Image::IMAGE_WATER_SOUTHEAST, $offset = 0, $angle = 0)
	{
		if (empty($this->img)) E('没有可以被写入文字的图像资源');
		if (!is_file($font)) E("不存在的字体文件：{$font}");
		if (is_array($color)) {
			$color = array_map('dechex', $color);
			foreach ($color as &$value) {
				$value = str_pad($value, 2, '0', STR_PAD_LEFT);
			}
			$color = '#' . implode('', $color);
		} elseif (!is_string($color) || 0 !== strpos($color, '#')) {
			E('错误的颜色值');
		}
		$col = substr($color, 0, 7);
		$alp = strlen($color) == 9 ? substr($color, -2) : 0;
		$draw = new \ImagickDraw();
		$draw->setFont(realpath($font));
		$draw->setFontSize($size);
		$draw->setFillColor($col);
		$draw->setFillAlpha(1 - hexdec($alp) / 127);
		$draw->setTextAntialias(true);
		$draw->setStrokeAntialias(true);
		$metrics = $this->img->queryFontMetrics($draw, $text);
		$x = 0;
		$y = $metrics['ascender'];
		$w = $metrics['textWidth'];
		$h = $metrics['textHeight'];
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
		if ('gif' == $this->info['type']) {
			$img = $this->img->coalesceImages();
			$this->img->destroy();
			do {
				$img->annotateImage($draw, $x + $ox, $y + $oy, $angle, $text);
			} while ($img->nextImage());
			$this->img = $img->deconstructImages();
			$img->destroy();
		} else {
			$this->img->annotateImage($draw, $x + $ox, $y + $oy, $angle, $text);
		}
		$draw->destroy();
	}

	public function __destruct()
	{
		empty($this->img) || $this->img->destroy();
	}
}