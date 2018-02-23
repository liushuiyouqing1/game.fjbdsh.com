<?php
namespace Aliyun\OSS\Models;
class CopyObjectResult
{
	private $lastModified;
	private $eTag;

	public function setETag($eTag)
	{
		$this->eTag = $eTag;
	}

	public function getETag()
	{
		return $this->eTag;
	}

	public function setLastModified($lastModified)
	{
		$this->lastModified = $lastModified;
	}

	public function getLastModified()
	{
		return $this->lastModified;
	}
}