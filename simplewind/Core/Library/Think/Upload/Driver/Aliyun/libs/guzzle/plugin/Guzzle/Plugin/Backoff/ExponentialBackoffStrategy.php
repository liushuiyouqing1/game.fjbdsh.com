<?php
namespace Guzzle\Plugin\Backoff;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\HttpException;

class ExponentialBackoffStrategy extends AbstractBackoffStrategy
{
	public function makesDecision()
	{
		return false;
	}

	protected function getDelay($retries, RequestInterface $request, Response $response = null, HttpException $e = null)
	{
		return (int)pow(2, $retries);
	}
} 