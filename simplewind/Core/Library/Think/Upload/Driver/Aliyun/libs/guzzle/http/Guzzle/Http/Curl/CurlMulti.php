<?php
namespace Guzzle\Http\Curl;

use Guzzle\Common\AbstractHasDispatcher;
use Guzzle\Common\Event;
use Guzzle\Http\Exception\MultiTransferException;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Message\RequestInterface;

class CurlMulti extends AbstractHasDispatcher implements CurlMultiInterface
{
	protected $multiHandle;
	protected $requests;
	protected $handles;
	protected $resourceHash;
	protected $exceptions = array();
	protected $successful = array();
	protected $multiErrors = array(CURLM_BAD_HANDLE => array('CURLM_BAD_HANDLE', 'The passed-in handle is not a valid CURLM handle.'), CURLM_BAD_EASY_HANDLE => array('CURLM_BAD_EASY_HANDLE', "An easy handle was not good/valid. It could mean that it isn't an easy handle at all, or possibly that the handle already is in used by this or another multi handle."), CURLM_OUT_OF_MEMORY => array('CURLM_OUT_OF_MEMORY', 'You are doomed.'), CURLM_INTERNAL_ERROR => array('CURLM_INTERNAL_ERROR', 'This can only be returned if libcurl bugs. Please report it to us!'));

	public function __construct()
	{
		$this->multiHandle = curl_multi_init();
		if ($this->multiHandle === false) {
			throw new CurlException('Unable to create multi handle');
		}
		$this->reset();
	}

	public function __destruct()
	{
		if (is_resource($this->multiHandle)) {
			curl_multi_close($this->multiHandle);
		}
	}

	public function add(RequestInterface $request)
	{
		$this->requests[] = $request;
		$this->beforeSend($request);
		$this->dispatch(self::ADD_REQUEST, array('request' => $request));
		return $this;
	}

	public function all()
	{
		return $this->requests;
	}

	public function remove(RequestInterface $request)
	{
		$this->removeHandle($request);
		foreach ($this->requests as $i => $r) {
			if ($request === $r) {
				unset($this->requests[$i]);
				$this->requests = array_values($this->requests);
				$this->dispatch(self::REMOVE_REQUEST, array('request' => $request));
				return true;
			}
		}
		return false;
	}

	public function reset($hard = false)
	{
		if ($this->requests) {
			foreach ($this->requests as $request) {
				$this->remove($request);
			}
		}
		$this->handles = new \SplObjectStorage();
		$this->requests = $this->resourceHash = $this->exceptions = $this->successful = array();
	}

	public function send()
	{
		$this->perform();
		$exceptions = $this->exceptions;
		$successful = $this->successful;
		$this->reset();
		if ($exceptions) {
			$this->throwMultiException($exceptions, $successful);
		}
	}

	public function count()
	{
		return count($this->requests);
	}

	protected function throwMultiException(array $exceptions, array $successful)
	{
		$multiException = new MultiTransferException('Errors during multi transfer');
		while ($e = array_shift($exceptions)) {
			$multiException->add($e['exception']);
			$multiException->addFailedRequest($e['request']);
		}
		foreach ($successful as $request) {
			if (!$multiException->containsRequest($request)) {
				$multiException->addSuccessfulRequest($request);
			}
		}
		throw $multiException;
	}

	protected function beforeSend(RequestInterface $request)
	{
		try {
			$state = $request->setState(RequestInterface::STATE_TRANSFER);
			if ($state == RequestInterface::STATE_TRANSFER) {
				$this->checkCurlResult(curl_multi_add_handle($this->multiHandle, $this->createCurlHandle($request)->getHandle()));
			} else {
				$this->remove($request);
				if ($state == RequestInterface::STATE_COMPLETE) {
					$this->successful[] = $request;
				}
			}
		} catch (\Exception $e) {
			$this->removeErroredRequest($request, $e);
		}
	}

	protected function createCurlHandle(RequestInterface $request)
	{
		$wrapper = CurlHandle::factory($request);
		$this->handles[$request] = $wrapper;
		$this->resourceHash[(int)$wrapper->getHandle()] = $request;
		return $wrapper;
	}

	protected function perform()
	{
		if (!$this->requests) {
			return;
		}
		$active = $mrc = null;
		$this->executeHandles($active, $mrc, 0.001);
		$event = new Event(array('curl_multi' => $this));
		$this->processMessages();
		while ($this->requests) {
			$blocking = $total = 0;
			foreach ($this->requests as $request) {
				++$total;
				$event['request'] = $request;
				$request->getEventDispatcher()->dispatch(self::POLLING_REQUEST, $event);
				if ($request->getParams()->hasKey(self::BLOCKING)) {
					++$blocking;
				}
			}
			if ($blocking == $total) {
				usleep(500);
			} else {
				do {
					$this->executeHandles($active, $mrc, 1);
				} while ($active);
			}
			$this->processMessages();
		}
	}

	private function processMessages()
	{
		while ($done = curl_multi_info_read($this->multiHandle)) {
			try {
				$request = $this->resourceHash[(int)$done['handle']];
				$this->processResponse($request, $this->handles[$request], $done);
				$this->successful[] = $request;
			} catch (MultiTransferException $e) {
				$this->removeErroredRequest($request, $e, false);
				throw $e;
			} catch (\Exception $e) {
				$this->removeErroredRequest($request, $e);
			}
		}
	}

	private function executeHandles(&$active, &$mrc, $timeout = 1)
	{
		do {
			$mrc = curl_multi_exec($this->multiHandle, $active);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM && $active);
		$this->checkCurlResult($mrc);
		if ($active && $mrc == CURLM_OK && curl_multi_select($this->multiHandle, $timeout) == -1) {
			usleep(100);
		}
	}

	protected function removeErroredRequest(RequestInterface $request, \Exception $e = null, $buffer = true)
	{
		if ($buffer) {
			$this->exceptions[] = array('request' => $request, 'exception' => $e);
		}
		$this->remove($request);
		$this->dispatch(self::MULTI_EXCEPTION, array('exception' => $e, 'all_exceptions' => $this->exceptions));
	}

	protected function processResponse(RequestInterface $request, CurlHandle $handle, array $curl)
	{
		$handle->updateRequestFromTransfer($request);
		$curlException = $this->isCurlException($request, $handle, $curl);
		$this->removeHandle($request);
		if (!$curlException) {
			$state = $request->setState(RequestInterface::STATE_COMPLETE, array('handle' => $handle));
			if ($state != RequestInterface::STATE_TRANSFER) {
				$this->remove($request);
			}
		} else {
			$state = $request->setState(RequestInterface::STATE_ERROR, array('exception' => $curlException));
			if ($state != RequestInterface::STATE_TRANSFER) {
				$this->remove($request);
			}
			if ($state == RequestInterface::STATE_ERROR) {
				throw $curlException;
			}
		}
	}

	protected function removeHandle(RequestInterface $request)
	{
		if (isset($this->handles[$request])) {
			$handle = $this->handles[$request];
			unset($this->handles[$request]);
			unset($this->resourceHash[(int)$handle->getHandle()]);
			curl_multi_remove_handle($this->multiHandle, $handle->getHandle());
			$handle->close();
		}
	}

	private function isCurlException(RequestInterface $request, CurlHandle $handle, array $curl)
	{
		if (CURLM_OK == $curl['result'] || CURLM_CALL_MULTI_PERFORM == $curl['result']) {
			return false;
		}
		$handle->setErrorNo($curl['result']);
		$e = new CurlException(sprintf('[curl] %s: %s [url] %s', $handle->getErrorNo(), $handle->getError(), $handle->getUrl()));
		$e->setCurlHandle($handle)->setRequest($request)->setCurlInfo($handle->getInfo())->setError($handle->getError(), $handle->getErrorNo());
		return $e;
	}

	private function checkCurlResult($code)
	{
		if ($code != CURLM_OK && $code != CURLM_CALL_MULTI_PERFORM) {
			throw new CurlException(isset($this->multiErrors[$code]) ? "cURL error: {$code} ({$this->multiErrors[$code][0]}): cURL message: {$this->multiErrors[$code][1]}" : 'Unexpected cURL error: ' . $code);
		}
	}
} 