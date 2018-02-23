<?php
namespace Aliyun\OSS\Parsers\SXParser;

use Aliyun\Common\Communication\HttpResponse;
use Aliyun\OSS\Models\InitiateMultipartUploadResult;

class SXInitiateMultipartUploadParser extends SXParser
{
	public function parse(HttpResponse $response, $options)
	{
		$xml = $this->getXmlObject($response->getContent());
		$result = new InitiateMultipartUploadResult();
		$result->setBucketName((string)$xml->Bucket);
		$result->setKey((string)$xml->Key);
		$result->setUploadId((string)$xml->UploadId);
		return $result;
	}
}