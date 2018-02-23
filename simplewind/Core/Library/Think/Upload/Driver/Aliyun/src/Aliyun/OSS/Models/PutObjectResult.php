<?php
namespace Aliyun\OSS\Models;
class PutObjectResult
{
	private $eTag;

	public function setETag($eTag)
	{
		$this->eTag = $eTag;
	}

	public function getETag()
	{
		return $this->eTag;
	}
}