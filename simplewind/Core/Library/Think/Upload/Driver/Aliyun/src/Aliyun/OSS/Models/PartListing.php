<?php
namespace Aliyun\OSS\Models;
class PartListing
{
	private $bucketName;
	private $key;
	private $uploadId;
	private $partNumberMarker;
	private $nextPartNumberMarker;
	private $maxParts;
	private $isTruncated;
	private $storageClass;
	private $parts = array();

	public function setBucketName($bucketName)
	{
		$this->bucketName = $bucketName;
	}

	public function getBucketName()
	{
		return $this->bucketName;
	}

	public function setKey($key)
	{
		$this->key = $key;
	}

	public function getKey()
	{
		return $this->key;
	}

	public function setIsTruncated($isTruncated)
	{
		$this->isTruncated = $isTruncated;
	}

	public function getIsTruncated()
	{
		return $this->isTruncated;
	}

	public function setMaxParts($maxParts)
	{
		$this->maxParts = $maxParts;
	}

	public function getMaxParts()
	{
		return $this->maxParts;
	}

	public function setNextPartNumberMarker($nextPartNumberMarker)
	{
		$this->nextPartNumberMarker = $nextPartNumberMarker;
	}

	public function getNextPartNumberMarker()
	{
		return $this->nextPartNumberMarker;
	}

	public function setPartNumberMarker($partNumberMarker)
	{
		$this->partNumberMarker = $partNumberMarker;
	}

	public function getPartNumberMarker()
	{
		return $this->partNumberMarker;
	}

	public function setParts($parts)
	{
		$this->parts = $parts;
	}

	public function getParts()
	{
		return $this->parts;
	}

	public function setUploadId($uploadId)
	{
		$this->uploadId = $uploadId;
	}

	public function getUploadId()
	{
		return $this->uploadId;
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