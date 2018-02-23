<?php
namespace Aliyun\OSS\ResponseHandlers;

use Aliyun\OSS\Utilities\OSSResponseParserFactory;
use Aliyun\OSS\Utilities\OSSExceptionFactory;
use Aliyun\Common\Communication\HttpResponse;
use Aliyun\Common\Communication\ResponseHandlerInterface;

class OSSErrorResponseHandler implements ResponseHandlerInterface
{
	public function handle(HttpResponse $response)
	{
		if ($response->isSuccess()) {
			return;
		}
		if (!$response->getContent() || $response->getContentLength() <= 0) {
			throw OSSExceptionFactory::factory()->createInvalidResponseException('ServerReturnsUnknownError');
		}
		$error = OSSResponseParserFactory::factory()->createErrorParser()->parse($response, null);
		throw OSSExceptionFactory::factory()->createFromError($error);
	}
} 