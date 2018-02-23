<?php
namespace Guzzle\Plugin\Backoff;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\HttpException;

class ReasonPhraseBackoffStrategy extends AbstractErrorCodeBackoffStrategy
{
	public function makesDecision()
	{
		return true;
	}

	protected function getDelay($retries, RequestInterface $request, Response $response = null, HttpException $e = null)
	{
		if ($response) {
			return isset($this->errorCodes[$response->getReasonPhrase()]) ? true : null;
		}
	}
} 