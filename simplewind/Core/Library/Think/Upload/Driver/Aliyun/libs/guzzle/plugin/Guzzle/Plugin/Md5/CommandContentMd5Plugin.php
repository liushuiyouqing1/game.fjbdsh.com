<?php
namespace Guzzle\Plugin\Md5;

use Guzzle\Common\Event;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CommandContentMd5Plugin implements EventSubscriberInterface
{
	protected $contentMd5Param;
	protected $validateMd5Param;

	public function __construct($contentMd5Param = 'ContentMD5', $validateMd5Param = 'ValidateMD5')
	{
		$this->contentMd5Param = $contentMd5Param;
		$this->validateMd5Param = $validateMd5Param;
	}

	public static function getSubscribedEvents()
	{
		return array('command.before_send' => array('onCommandBeforeSend', -255));
	}

	public function onCommandBeforeSend(Event $event)
	{
		$command = $event['command'];
		$request = $command->getRequest();
		if ($request instanceof EntityEnclosingRequestInterface && $request->getBody() && $command->getOperation()->hasParam($this->contentMd5Param)) {
			if ($command[$this->contentMd5Param] === true) {
				if (false !== ($md5 = $request->getBody()->getContentMd5(true, true))) {
					$request->setHeader('Content-MD5', $md5);
				}
			}
		}
		if ($command[$this->validateMd5Param] === true) {
			$request->addSubscriber(new Md5ValidatorPlugin(true, false));
		}
	}
} 