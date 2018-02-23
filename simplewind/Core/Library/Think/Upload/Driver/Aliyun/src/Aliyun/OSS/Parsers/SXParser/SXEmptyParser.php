<?php
namespace Aliyun\OSS\Parsers\SXParser;

use Aliyun\Common\Communication\ResponseParserInterface;
use Aliyun\Common\Communication\HttpResponse;

class SXEmptyParser implements ResponseParserInterface
{
	public function parse(HttpResponse $response, $options)
	{
	}
}