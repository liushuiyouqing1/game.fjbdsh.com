<?php
namespace Guzzle\Plugin\Backoff;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\HttpException;

class LinearBackoffStrategy extends AbstractBackoffStrategy
{
	protected $step;

	public function __construct($step = 1)
	{
		$this->step = $step;
	}

	public function makesDecision()
	{
		return false;
	}

	protected function getDelay($retries, RequestInterface $request, Response $response = null, HttpException $e = null)
	{
		return $retries * $this->step;
	}
} 