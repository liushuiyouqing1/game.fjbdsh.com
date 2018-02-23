<?php
namespace Aliyun\OSS;

use Aliyun\Common\Utilities\AssertUtils;
use Aliyun\Common\Resources\ResourceManager;
use Aliyun\Common\Communication\ServiceClientFactory;
use Aliyun\OSS\Commands\GeneratePresignedUrlCommand;
use Aliyun\OSS\Models\OSSOptions;

class OSSClient
{
	protected $endpoint;
	protected $credentials;
	protected $serviceClient;

	public static function factory(array $config)
	{
		return new static($config);
	}

	public function getEndpoint()
	{
		return $this->endpoint;
	}

	public function getCredentials()
	{
		return $this->credentials;
	}

	public function listBuckets(array $options = array())
	{
		return $this->execute(__FUNCTION__, $options);
	}

	public function createBucket(array $options)
	{
		return $this->execute(__FUNCTION__, $options);
	}

	public function deleteBucket(array $options)
	{
		return $this->execute(__FUNCTION__, $options);
	}

	public function getBucketAcl(array $options)
	{
		return $this->execute(__FUNCTION__, $options);
	}

	public function setBucketAcl(array $options)
	{
		return $this->execute(__FUNCTION__, $options);
	}

	public function putObject(array $options)
	{
		return $this->execute(__FUNCTION__, $options);
	}

	public function listObjects(array $options)
	{
		return $this->execute(__FUNCTION__, $options);
	}

	public function getObject(array $options)
	{
		return $this->execute(__FUNCTION__, $options);
	}

	public function getObjectMetadata(array $options)
	{
		$options[OSSOptions::META_ONLY] = true;
		return $this->getObject($options);
	}

	public function deleteObject(array $options)
	{
		return $this->execute(__FUNCTION__, $options);
	}

	public function copyObject(array $options)
	{
		return $this->execute(__FUNCTION__, $options);
	}

	public function initiateMultipartUpload(array $options)
	{
		return $this->execute(__FUNCTION__, $options);
	}

	public function listMultipartUploads(array $options)
	{
		return $this->execute(__FUNCTION__, $options);
	}

	public function uploadPart(array $options)
	{
		return $this->execute(__FUNCTION__, $options);
	}

	public function listParts(array $options)
	{
		return $this->execute(__FUNCTION__, $options);
	}

	public function abortMultipartUpload(array $options)
	{
		return $this->execute(__FUNCTION__, $options);
	}

	public function completeMultipartUpload(array $options)
	{
		return $this->execute(__FUNCTION__, $options);
	}

	public function generatePresignedUrl(array $options)
	{
		$command = new GeneratePresignedUrlCommand(__FUNCTION__);
		return $command->execute($this->getClientOptions(), $options);
	}

	protected function __construct(array $config)
	{
		$config = array_merge(ResourceManager::getInstance()->getDefaultOptions(__DIR__), $config);
		AssertUtils::assertSet(array(OSSOptions::ENDPOINT, OSSOptions::ACCESS_KEY_ID, OSSOptions::ACCESS_KEY_SECRET,), $config);
		$this->endpoint = $config[OSSOptions::ENDPOINT];
		$this->credentials = array(OSSOptions::ACCESS_KEY_ID => $config[OSSOptions::ACCESS_KEY_ID], OSSOptions::ACCESS_KEY_SECRET => $config[OSSOptions::ACCESS_KEY_SECRET],);
		$this->serviceClient = ServiceClientFactory::factory()->createService($config);
	}

	protected function getClientOptions()
	{
		return array(OSSOptions::ENDPOINT => $this->endpoint, OSSOptions::ACCESS_KEY_ID => $this->credentials[OSSOptions::ACCESS_KEY_ID], OSSOptions::ACCESS_KEY_SECRET => $this->credentials[OSSOptions::ACCESS_KEY_SECRET],);
	}

	protected function execute($method, $options)
	{
		$className = ucfirst($method) . 'Command';
		$class = 'Aliyun\\OSS\\Commands\\' . $className;
		$clientOptions = $this->getClientOptions();
		$command = new $class($method);
		$command->setService($this->serviceClient);
		return $command->execute($clientOptions, $options);
	}
} 