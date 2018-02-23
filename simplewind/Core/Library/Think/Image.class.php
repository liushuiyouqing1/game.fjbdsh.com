<?php
namespace Think;
class Image
{
	const IMAGE_GD = 1;
	const IMAGE_IMAGICK = 2;
	const IMAGE_THUMB_SCALE = 1;
	const IMAGE_THUMB_FILLED = 2;
	const IMAGE_THUMB_CENTER = 3;
	const IMAGE_THUMB_NORTHWEST = 4;
	const IMAGE_THUMB_SOUTHEAST = 5;
	const IMAGE_THUMB_FIXED = 6;
	const IMAGE_WATER_NORTHWEST = 1;
	const IMAGE_WATER_NORTH = 2;
	const IMAGE_WATER_NORTHEAST = 3;
	const IMAGE_WATER_WEST = 4;
	const IMAGE_WATER_CENTER = 5;
	const IMAGE_WATER_EAST = 6;
	const IMAGE_WATER_SOUTHWEST = 7;
	const IMAGE_WATER_SOUTH = 8;
	const IMAGE_WATER_SOUTHEAST = 9;
	private $img;

	public function __construct($type = self::IMAGE_GD, $imgname = null)
	{
		switch ($type) {
			case self::IMAGE_GD:
				$class = 'Gd';
				break;
			case self::IMAGE_IMAGICK:
				$class = 'Imagick';
				break;
			default:
				E('不支持的图片处理库类型');
		}
		$class = "Think\\Image\\Driver\\{$class}";
		$this->img = new $class($imgname);
	}

	public function open($imgname)
	{
		$this->img->open($imgname);
		return $this;
	}

	public function save($imgname, $type = null, $quality = 80, $interlace = true)
	{
		$this->img->save($imgname, $type, $quality, $interlace);
		return $this;
	}

	public function width()
	{
		return $this->img->width();
	}

	public function height()
	{
		return $this->img->height();
	}

	public function type()
	{
		return $this->img->type();
	}

	public function mime()
	{
		return $this->img->mime();
	}

	public function size()
	{
		return $this->img->size();
	}

	public function crop($w, $h, $x = 0, $y = 0, $width = null, $height = null)
	{
		$this->img->crop($w, $h, $x, $y, $width, $height);
		return $this;
	}

	public function thumb($width, $height, $type = self::IMAGE_THUMB_SCALE)
	{
		$this->img->thumb($width, $height, $type);
		return $this;
	}

	public function water($source, $locate = self::IMAGE_WATER_SOUTHEAST, $alpha = 80)
	{
		$this->img->water($source, $locate, $alpha);
		return $this;
	}

	public function text($text, $font, $size, $color = '#00000000', $locate = self::IMAGE_WATER_SOUTHEAST, $offset = 0, $angle = 0)
	{
		$this->img->text($text, $font, $size, $color, $locate, $offset, $angle);
		return $this;
	}
}