<?php
namespace Aliyun\OSS\Models;
class MultipartUploadsListing
{
	private $bucketName;
	private $keyMarker;
	private $delimiter;
	private $prefix;
	private $uploadIdMarker;
	private $maxUploads;
	private $isTruncated;
	private $nextKeyMarker;
	private $nextUploadIdMarker;
	private $multipartUploads = array();
	private $commonPrefixes = array();

	public function setUploadIdMarker($uploadIdMarker)
	{
		$this->uploadIdMarker = $uploadIdMarker;
	}

	public function getUploadIdMarker()
	{
		return $this->uploadIdMarker;
	}

	public function setBucketName($bucketName)
	{
		$this->bucketName = $bucketName;
	}

	public function getBucketName()
	{
		return $this->bucketName;
	}

	public function setCommonPrefixes($commonPrefixes)
	{
		$this->commonPrefixes = $commonPrefixes;
	}

	public function getCommonPrefixes()
	{
		return $this->commonPrefixes;
	}

	public function setDelimiter($delimiter)
	{
		$this->delimiter = $delimiter;
	}

	public function getDelimiter()
	{
		return $this->delimiter;
	}

	public function setIsTruncated($isTruncated)
	{
		$this->isTruncated = $isTruncated;
	}

	public function getIsTruncated()
	{
		return $this->isTruncated;
	}

	public function setKeyMarker($keyMarker)
	{
		$this->keyMarker = $keyMarker;
	}

	public function getKeyMarker()
	{
		return $this->keyMarker;
	}

	public function setMaxUploads($maxUploads)
	{
		$this->maxUploads = $maxUploads;
	}

	public function getMaxUploads()
	{
		return $this->maxUploads;
	}

	public function setMultipartUploads($multipartUploads)
	{
		$this->multipartUploads = $multipartUploads;
	}

	public function getMultipartUploads()
	{
		return $this->multipartUploads;
	}

	public function setNextKeyMarker($nextKeyMarker)
	{
		$this->nextKeyMarker = $nextKeyMarker;
	}

	public function getNextKeyMarker()
	{
		return $this->nextKeyMarker;
	}

	public function setNextUploadIdMarker($nextUploadIdMarker)
	{
		$this->nextUploadIdMarker = $nextUploadIdMarker;
	}

	public function getNextUploadIdMarker()
	{
		return $this->nextUploadIdMarker;
	}

	public function setPrefix($prefix)
	{
		$this->prefix = $prefix;
	}

	public function getPrefix()
	{
		return $this->prefix;
	}
}