<?php
namespace Guzzle\Plugin\Backoff;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\HttpException;

interface BackoffStrategyInterface
{
	public function getBackoffPeriod($retries, RequestInterface $request, Response $response = null, HttpException $e = null);
} 