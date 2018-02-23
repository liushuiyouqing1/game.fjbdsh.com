<?php
namespace Guzzle\Plugin\Backoff;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\HttpException;

class HttpBackoffStrategy extends AbstractErrorCodeBackoffStrategy
{
	protected static $defaultErrorCodes = array(500, 503);

	protected function getDelay($retries, RequestInterface $request, Response $response = null, HttpException $e = null)
	{
		if ($response) {
			if ($response->isSuccessful()) {
				return false;
			} else {
				return isset($this->errorCodes[$response->getStatusCode()]) ? true : null;
			}
		}
	}
} 