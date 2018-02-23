<?php
namespace Guzzle\Http\Curl;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\EntityBody;
use Guzzle\Http\Message\Response;

class RequestMediator
{
	protected $request;
	protected $emitIo;

	public function __construct(RequestInterface $request, $emitIo = false)
	{
		$this->request = $request;
		$this->emitIo = $emitIo;
	}

	public function receiveResponseHeader($curl, $header)
	{
		static $normalize = array("\r", "\n");
		$length = strlen($header);
		$header = str_replace($normalize, '', $header);
		if (strpos($header, 'HTTP/') === 0) {
			$startLine = explode(' ', $header, 3);
			$code = $startLine[1];
			$status = isset($startLine[2]) ? $startLine[2] : '';
			if ($code >= 200 && $code < 300) {
				$body = $this->request->getResponseBody();
			} else {
				$body = EntityBody::factory();
			}
			$response = new Response($code, null, $body);
			$response->setStatus($code, $status);
			$this->request->startResponse($response);
			$this->request->dispatch('request.receive.status_line', array('request' => $this, 'line' => $header, 'status_code' => $code, 'reason_phrase' => $status));
		} elseif ($pos = strpos($header, ':')) {
			$this->request->getResponse()->addHeader(trim(substr($header, 0, $pos)), trim(substr($header, $pos + 1)));
		}
		return $length;
	}

	public function progress($downloadSize, $downloaded, $uploadSize, $uploaded, $handle = null)
	{
		$this->request->dispatch('curl.callback.progress', array('request' => $this->request, 'handle' => $handle, 'download_size' => $downloadSize, 'downloaded' => $downloaded, 'upload_size' => $uploadSize, 'uploaded' => $uploaded));
	}

	public function writeResponseBody($curl, $write)
	{
		if ($this->emitIo) {
			$this->request->dispatch('curl.callback.write', array('request' => $this->request, 'write' => $write));
		}
		return $this->request->getResponse()->getBody()->write($write);
	}

	public function readRequestBody($ch, $fd, $length)
	{
		if (!($body = $this->request->getBody())) {
			return '';
		}
		$read = (string)$body->read($length);
		if ($this->emitIo) {
			$this->request->dispatch('curl.callback.read', array('request' => $this->request, 'read' => $read));
		}
		return $read;
	}
} 