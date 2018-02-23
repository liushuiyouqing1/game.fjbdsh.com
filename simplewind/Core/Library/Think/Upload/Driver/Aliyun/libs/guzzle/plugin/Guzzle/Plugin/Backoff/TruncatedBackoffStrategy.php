<?php
namespace Guzzle\Plugin\Backoff;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\HttpException;

class TruncatedBackoffStrategy extends AbstractBackoffStrategy
{
	protected $max;

	public function __construct($maxRetries, BackoffStrategyInterface $next = null)
	{
		$this->max = $maxRetries;
		$this->next = $next;
	}

	public function makesDecision()
	{
		return true;
	}

	protected function getDelay($retries, RequestInterface $request, Response $response = null, HttpException $e = null)
	{
		return $retries < $this->max ? null : false;
	}
} 