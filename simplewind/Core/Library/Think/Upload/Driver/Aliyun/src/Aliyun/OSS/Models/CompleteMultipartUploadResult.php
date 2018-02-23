<?php
namespace Aliyun\OSS\Models;
class CompleteMultipartUploadResult
{
	private $location;
	private $bucketName;
	private $key;
	private $eTag;

	public function setBucketName($bucketName)
	{
		$this->bucketName = $bucketName;
	}

	public function getBucketName()
	{
		return $this->bucketName;
	}

	public function setETag($eTag)
	{
		$this->eTag = $eTag;
	}

	public function getETag()
	{
		return $this->eTag;
	}

	public function setKey($key)
	{
		$this->key = $key;
	}

	public function getKey()
	{
		return $this->key;
	}

	public function setLocation($location)
	{
		$this->location = $location;
	}

	public function getLocation()
	{
		return $this->location;
	}
} 