<?php
namespace Aliyun\OSS\Models;
class InitiateMultipartUploadResult
{
	private $bucketName;
	private $key;
	private $uploadId;

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

	public function setUploadId($uploadId)
	{
		$this->uploadId = $uploadId;
	}

	public function getUploadId()
	{
		return $this->uploadId;
	}
}