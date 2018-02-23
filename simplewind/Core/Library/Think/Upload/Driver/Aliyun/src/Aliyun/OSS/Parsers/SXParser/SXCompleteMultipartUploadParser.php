<?php
namespace Aliyun\OSS\Parsers\SXParser;

use Aliyun\Common\Communication\HttpResponse;
use Aliyun\OSS\Models\CompleteMultipartUploadResult;
use Aliyun\OSS\Utilities\OSSUtils;

class SXCompleteMultipartUploadParser extends SXParser
{
	public function parse(HttpResponse $response, $options)
	{
		$xml = $this->getXmlObject($response->getContent());
		$result = new CompleteMultipartUploadResult();
		$result->setETag(OSSUtils::trimQuotes((string)$xml->ETag));
		$result->setLocation((string)$xml->Location);
		$result->setBucketName((string)$xml->Bucket);
		$result->setKey((string)$xml->Key);
		return $result;
	}
}