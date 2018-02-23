<?php
namespace Guzzle\Plugin\Mock;

use Guzzle\Common\Event;
use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Common\AbstractHasDispatcher;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Message\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MockPlugin extends AbstractHasDispatcher implements EventSubscriberInterface, \Countable
{
	protected $queue = array();
	protected $temporary = false;
	protected $received = array();
	protected $readBodies;

	public function __construct(array $items = null, $temporary = false, $readBodies = false)
	{
		$this->readBodies = $readBodies;
		$this->temporary = $temporary;
		if ($items) {
			foreach ($items as $item) {
				if ($item instanceof \Exception) {
					$this->addException($item);
				} else {
					$this->addResponse($item);
				}
			}
		}
	}

	public static function getSubscribedEvents()
	{
		return array('request.before_send' => array('onRequestBeforeSend', -999));
	}

	public static function getAllEvents()
	{
		return array('mock.request');
	}

	public static function getMockFile($path)
	{
		if (!file_exists($path)) {
			throw new InvalidArgumentException('Unable to open mock file: ' . $path);
		}
		return Response::fromMessage(file_get_contents($path));
	}

	public function readBodies($readBodies)
	{
		$this->readBodies = $readBodies;
		return $this;
	}

	public function count()
	{
		return count($this->queue);
	}

	public function addResponse($response)
	{
		if (!($response instanceof Response)) {
			if (!is_string($response)) {
				throw new InvalidArgumentException('Invalid response');
			}
			$response = self::getMockFile($response);
		}
		$this->queue[] = $response;
		return $this;
	}

	public function addException(CurlException $e)
	{
		$this->queue[] = $e;
		return $this;
	}

	public function clearQueue()
	{
		$this->queue = array();
		return $this;
	}

	public function getQueue()
	{
		return $this->queue;
	}

	public function isTemporary()
	{
		return $this->temporary;
	}

	public function dequeue(RequestInterface $request)
	{
		$this->dispatch('mock.request', array('plugin' => $this, 'request' => $request));
		$item = array_shift($this->queue);
		if ($item instanceof Response) {
			if ($this->readBodies && $request instanceof EntityEnclosingRequestInterface) {
				$request->getEventDispatcher()->addListener('request.sent', $f = function (Event $event) use (&$f) {
					while ($data = $event['request']->getBody()->read(8096)) ;
					$event['request']->getEventDispatcher()->removeListener('request.sent', $f);
				});
			}
			$request->setResponse($item);
		} elseif ($item instanceof CurlException) {
			$item->setRequest($request);
			$state = $request->setState(RequestInterface::STATE_ERROR, array('exception' => $item));
			if ($state == RequestInterface::STATE_ERROR) {
				throw $item;
			}
		}
		return $this;
	}

	public function flush()
	{
		$this->received = array();
	}

	public function getReceivedRequests()
	{
		return $this->received;
	}

	public function onRequestBeforeSend(Event $event)
	{
		if ($this->queue) {
			$request = $event['request'];
			$this->received[] = $request;
			if ($this->temporary && count($this->queue) == 1 && $request->getClient()) {
				$request->getClient()->getEventDispatcher()->removeSubscriber($this);
			}
			$this->dequeue($request);
		}
	}
} 