<?php
namespace Aliyun\OSS\Models;
class OSSObjectSummary
{
	private $bucketName;
	private $key;
	private $eTag;
	private $size;
	private $lastModified;
	private $storageClass;
	private $owner;

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

	public function setLastModified($lastModified)
	{
		$this->lastModified = $lastModified;
	}

	public function getLastModified()
	{
		return $this->lastModified;
	}

	public function setOwner(Owner $owner)
	{
		$this->owner = $owner;
	}

	public function getOwner()
	{
		return $this->owner;
	}

	public function setSize($size)
	{
		$this->size = $size;
	}

	public function getSize()
	{
		return $this->size;
	}

	public function setStorageClass($storageClass)
	{
		$this->storageClass = $storageClass;
	}

	public function getStorageClass()
	{
		return $this->storageClass;
	}
}