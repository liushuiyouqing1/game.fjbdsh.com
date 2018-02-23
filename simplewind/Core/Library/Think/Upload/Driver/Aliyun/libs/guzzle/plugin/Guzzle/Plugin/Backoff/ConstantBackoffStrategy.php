<?php
namespace Guzzle\Plugin\Backoff;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\HttpException;

class ConstantBackoffStrategy extends AbstractBackoffStrategy
{
	protected $delay;

	public function __construct($delay)
	{
		$this->delay = $delay;
	}

	public function makesDecision()
	{
		return false;
	}

	protected function getDelay($retries, RequestInterface $request, Response $response = null, HttpException $e = null)
	{
		return $this->delay;
	}
} 