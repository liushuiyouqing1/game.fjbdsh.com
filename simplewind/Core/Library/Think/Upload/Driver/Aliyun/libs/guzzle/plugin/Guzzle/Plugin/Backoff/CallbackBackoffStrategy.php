<?php
namespace Guzzle\Plugin\Backoff;

use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\HttpException;

class CallbackBackoffStrategy extends AbstractBackoffStrategy
{
	protected $callback;
	protected $decision;

	public function __construct($callback, $decision, BackoffStrategyInterface $next = null)
	{
		if (!is_callable($callback)) {
			throw new InvalidArgumentException('The callback must be callable');
		}
		$this->callback = $callback;
		$this->decision = (bool)$decision;
		$this->next = $next;
	}

	public function makesDecision()
	{
		return $this->decision;
	}

	protected function getDelay($retries, RequestInterface $request, Response $response = null, HttpException $e = null)
	{
		return call_user_func($this->callback, $retries, $request, $response, $e);
	}
} 