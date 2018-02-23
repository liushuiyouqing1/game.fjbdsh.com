<?php
namespace Aliyun\Common\Communication;

use Aliyun\Common\Communication\HttpServiceClient;
use Aliyun\Common\Communication\OpenServiceClient;
use Aliyun\Common\Models\ServiceOptions;

class ServiceClientFactory
{
	public static function factory()
	{
		return new static();
	}

	public function createService($config)
	{
		$httpClient = new HttpServiceClient(array(ServiceOptions::CURL_OPTIONS => $config[ServiceOptions::CURL_OPTIONS],));
		$openServiceClient = new OpenServiceClient($httpClient, array(ServiceOptions::USER_AGENT => $config[ServiceOptions::USER_AGENT],));
		$retryableClient = new RetryableServiceClient($openServiceClient, array(ServiceOptions::MAX_ERROR_RETRY => $config[ServiceOptions::MAX_ERROR_RETRY],));
		return $retryableClient;
	}
} 