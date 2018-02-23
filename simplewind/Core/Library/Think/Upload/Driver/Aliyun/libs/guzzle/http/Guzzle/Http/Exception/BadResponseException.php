<?php
namespace Guzzle\Http\Exception;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;

class BadResponseException extends RequestException
{
	private $response;

	public static function factory(RequestInterface $request, Response $response)
	{
		if ($response->isClientError()) {
			$label = 'Client error response';
			$class = __NAMESPACE__ . '\\ClientErrorResponseException';
		} elseif ($response->isServerError()) {
			$label = 'Server error response';
			$class = __NAMESPACE__ . '\\ServerErrorResponseException';
		} else {
			$label = 'Unsuccessful response';
			$class = __CLASS__;
			$e = new self();
		}
		$message = $label . PHP_EOL . implode(PHP_EOL, array('[status code] ' . $response->getStatusCode(), '[reason phrase] ' . $response->getReasonPhrase(), '[url] ' . $request->getUrl(),));
		$e = new $class($message);
		$e->setResponse($response);
		$e->setRequest($request);
		return $e;
	}

	public function setResponse(Response $response)
	{
		$this->response = $response;
	}

	public function getResponse()
	{
		return $this->response;
	}
} 