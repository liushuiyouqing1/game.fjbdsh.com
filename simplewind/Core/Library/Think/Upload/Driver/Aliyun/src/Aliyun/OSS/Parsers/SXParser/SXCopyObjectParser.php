<?php
namespace Aliyun\OSS\Parsers\SXParser;

use Aliyun\Common\Communication\HttpResponse;
use Aliyun\Common\Utilities\DateUtils;
use Aliyun\OSS\Models\CopyObjectResult;
use Aliyun\OSS\Utilities\OSSUtils;

class SXCopyObjectParser extends SXParser
{
	public function parse(HttpResponse $response, $options)
	{
		$xml = $this->getXmlObject($response->getContent());
		$lastModified = DateUtils::parseDate((string)$xml->LastModified);
		$eTag = OSSUtils::trimQuotes((string)$xml->ETag);
		$copyObjectResult = new CopyObjectResult();
		$copyObjectResult->setLastModified($lastModified);
		$copyObjectResult->setETag($eTag);
		return $copyObjectResult;
	}
}