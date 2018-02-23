<?php
namespace Guzzle\Http\Exception;

use Guzzle\Common\Exception\ExceptionCollection;
use Guzzle\Http\Message\RequestInterface;

class MultiTransferException extends ExceptionCollection
{
	protected $successfulRequests = array();
	protected $failedRequests = array();

	public function getAllRequests()
	{
		return array_merge($this->successfulRequests, $this->failedRequests);
	}

	public function addSuccessfulRequest(RequestInterface $request)
	{
		$this->successfulRequests[] = $request;
		return $this;
	}

	public function addFailedRequest(RequestInterface $request)
	{
		$this->failedRequests[] = $request;
		return $this;
	}

	public function setSuccessfulRequests(array $requests)
	{
		$this->successfulRequests = $requests;
		return $this;
	}

	public function setFailedRequests(array $requests)
	{
		$this->failedRequests = $requests;
		return $this;
	}

	public function getSuccessfulRequests()
	{
		return $this->successfulRequests;
	}

	public function getFailedRequests()
	{
		return $this->failedRequests;
	}

	public function containsRequest(RequestInterface $request)
	{
		return in_array($request, $this->failedRequests, true) || in_array($request, $this->successfulRequests, true);
	}
} 