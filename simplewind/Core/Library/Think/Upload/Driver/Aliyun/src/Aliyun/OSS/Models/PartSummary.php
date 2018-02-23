<?php
namespace Aliyun\OSS\Models;
class PartSummary
{
	private $partNumber;
	private $lastModified;
	private $eTag;
	private $size;

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

	public function setPartNumber($partNumber)
	{
		$this->partNumber = $partNumber;
	}

	public function getPartNumber()
	{
		return $this->partNumber;
	}

	public function setSize($size)
	{
		$this->size = $size;
	}

	public function getSize()
	{
		return $this->size;
	}
} 