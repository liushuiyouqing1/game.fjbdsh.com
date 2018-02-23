<?php
namespace Guzzle\Plugin\Log;

use Guzzle\Common\Event;
use Guzzle\Log\LogAdapterInterface;
use Guzzle\Log\MessageFormatter;
use Guzzle\Log\ClosureLogAdapter;
use Guzzle\Http\EntityBody;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Message\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LogPlugin implements EventSubscriberInterface
{
	private $logAdapter;
	protected $formatter;
	protected $wireBodies;

	public function __construct(LogAdapterInterface $logAdapter, $formatter = null, $wireBodies = false)
	{
		$this->logAdapter = $logAdapter;
		$this->formatter = $formatter instanceof MessageFormatter ? $formatter : new MessageFormatter($formatter);
		$this->wireBodies = $wireBodies;
	}

	public static function getDebugPlugin($wireBodies = true, $stream = null)
	{
		if ($stream === null) {
			if (defined('STDERR')) {
				$stream = STDERR;
			} else {
				$stream = fopen('php://output', 'w');
			}
		}
		return new self(new ClosureLogAdapter(function ($m) use ($stream) {
			fwrite($stream, $m . PHP_EOL);
		}), "# Request:\n{request}\n\n# Response:\n{response}\n\n# Errors: {curl_code} {curl_error}", $wireBodies);
	}

	public static function getSubscribedEvents()
	{
		return array('curl.callback.write' => array('onCurlWrite', 255), 'curl.callback.read' => array('onCurlRead', 255), 'request.before_send' => array('onRequestBeforeSend', 255), 'request.sent' => array('onRequestSent', 255));
	}

	public function onCurlRead(Event $event)
	{
		if ($wire = $event['request']->getParams()->get('request_wire')) {
			$wire->write($event['read']);
		}
	}

	public function onCurlWrite(Event $event)
	{
		if ($wire = $event['request']->getParams()->get('response_wire')) {
			$wire->write($event['write']);
		}
	}

	public function onRequestBeforeSend(Event $event)
	{
		if ($this->wireBodies) {
			$request = $event['request'];
			$request->getCurlOptions()->set('emit_io', true);
			if ($request instanceof EntityEnclosingRequestInterface && $request->getBody() && (!$request->getBody()->isSeekable() || !$request->getBody()->isReadable())) {
				$request->getParams()->set('request_wire', EntityBody::factory());
			}
			if (!$request->getResponseBody()->isRepeatable()) {
				$request->getParams()->set('response_wire', EntityBody::factory());
			}
		}
	}

	public function onRequestSent(Event $event)
	{
		$request = $event['request'];
		$response = $event['response'];
		$handle = $event['handle'];
		if ($wire = $request->getParams()->get('request_wire')) {
			$request = clone $request;
			$request->setBody($wire);
		}
		if ($wire = $request->getParams()->get('response_wire')) {
			$response = clone $response;
			$response->setBody($wire);
		}
		$priority = $response && $response->isError() ? LOG_ERR : LOG_DEBUG;
		$message = $this->formatter->format($request, $response, $handle);
		$this->logAdapter->log($message, $priority, array('request' => $request, 'response' => $response, 'handle' => $handle));
	}
} 