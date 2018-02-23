<?php
namespace Guzzle\Plugin\Backoff;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\HttpException;

abstract class AbstractBackoffStrategy implements BackoffStrategyInterface
{
	protected $next;

	public function setNext(AbstractBackoffStrategy $next)
	{
		$this->next = $next;
	}

	public function getNext()
	{
		return $this->next;
	}

	public function getBackoffPeriod($retries, RequestInterface $request, Response $response = null, HttpException $e = null)
	{
		$delay = $this->getDelay($retries, $request, $response, $e);
		if ($delay === false) {
			return false;
		} elseif ($delay === null) {
			return !$this->next || !$this->next->makesDecision() ? false : $this->next->getBackoffPeriod($retries, $request, $response, $e);
		} elseif ($delay === true) {
			if (!$this->next) {
				return 0;
			} else {
				$next = $this->next;
				while ($next->makesDecision() && $next->getNext()) {
					$next = $next->getNext();
				}
				return !$next->makesDecision() ? $next->getBackoffPeriod($retries, $request, $response, $e) : 0;
			}
		} else {
			return $delay;
		}
	}

	abstract public function makesDecision();

	abstract protected function getDelay($retries, RequestInterface $request, Response $response = null, HttpException $e = null);
} 