<?php
namespace Aliyun\OSS\Models;

use Aliyun\OSS\Models\OSSOptions;

class UploadPartResult
{
	private $partNumber;
	private $eTag;

	public function setETag($eTag)
	{
		$this->eTag = $eTag;
	}

	public function getETag()
	{
		return $this->eTag;
	}

	public function setPartNumber($partNumber)
	{
		$this->partNumber = $partNumber;
	}

	public function getPartNumber()
	{
		return $this->partNumber;
	}

	public function getPartETag()
	{
		return array(OSSOptions::PART_NUMBER => $this->partNumber, OSSOptions::ETAG => $this->eTag,);
	}
}