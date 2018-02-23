<?php
namespace Aliyun\OSS\Parsers\SXParser;

use Aliyun\Common\Communication\HttpResponse;
use Aliyun\OSS\Models\UploadPartResult;
use Aliyun\OSS\Utilities\OSSHeaders;
use Aliyun\Common\Communication\ResponseParserInterface;
use Aliyun\OSS\Models\OSSOptions;
use Aliyun\OSS\Utilities\OSSUtils;

class SXUploadPartParser implements ResponseParserInterface
{
	public function parse(HttpResponse $response, $options)
	{
		$result = new UploadPartResult();
		$result->setETag(OSSUtils::trimQuotes($response->getHeader(OSSHeaders::ETAG)));
		$result->setPartNumber($options[OSSOptions::PART_NUMBER]);
		return $result;
	}
}