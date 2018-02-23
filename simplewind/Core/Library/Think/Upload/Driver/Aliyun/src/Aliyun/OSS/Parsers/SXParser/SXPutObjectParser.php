<?php
namespace Aliyun\OSS\Parsers\SXParser;

use Aliyun\OSS\Models\PutObjectResult;
use Aliyun\Common\Communication\HttpResponse;
use Aliyun\OSS\Utilities\OSSHeaders;
use Aliyun\Common\Communication\ResponseParserInterface;
use Aliyun\OSS\Utilities\OSSUtils;

class SXPutObjectParser implements ResponseParserInterface
{
	public function parse(HttpResponse $response, $options)
	{
		$putObjectResult = new PutObjectResult();
		$putObjectResult->setETag(OSSUtils::trimQuotes($response->getHeader(OSSHeaders::ETAG)));
		return $putObjectResult;
	}
}