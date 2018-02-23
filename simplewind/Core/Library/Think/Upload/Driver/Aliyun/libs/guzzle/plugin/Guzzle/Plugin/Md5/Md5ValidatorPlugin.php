<?php
namespace Guzzle\Plugin\Md5;

use Guzzle\Common\Event;
use Guzzle\Common\Exception\UnexpectedValueException;
use Guzzle\Http\Message\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Md5ValidatorPlugin implements EventSubscriberInterface
{
	protected $contentLengthCutoff;
	protected $contentEncoded;

	public function __construct($contentEncoded = true, $contentLengthCutoff = false)
	{
		$this->contentLengthCutoff = $contentLengthCutoff;
		$this->contentEncoded = $contentEncoded;
	}

	public static function getSubscribedEvents()
	{
		return array('request.complete' => array('onRequestComplete', 255));
	}

	public function onRequestComplete(Event $event)
	{
		$response = $event['response'];
		if (!$contentMd5 = $response->getContentMd5()) {
			return;
		}
		$contentEncoding = $response->getContentEncoding();
		if ($contentEncoding && !$this->contentEncoded) {
			return false;
		}
		if ($this->contentLengthCutoff) {
			$size = $response->getContentLength() ?: $response->getBody()->getSize();
			if (!$size || $size > $this->contentLengthCutoff) {
				return;
			}
		}
		if (!$contentEncoding) {
			$hash = $response->getBody()->getContentMd5();
		} elseif ($contentEncoding == 'gzip') {
			$response->getBody()->compress('zlib.deflate');
			$hash = $response->getBody()->getContentMd5();
			$response->getBody()->uncompress();
		} elseif ($contentEncoding == 'compress') {
			$response->getBody()->compress('bzip2.compress');
			$hash = $response->getBody()->getContentMd5();
			$response->getBody()->uncompress();
		} else {
			return;
		}
		if ($contentMd5 !== $hash) {
			throw new UnexpectedValueException("The response entity body may have been modified over the wire.  The Content-MD5 " . "received ({$contentMd5}) did not match the calculated MD5 hash ({$hash}).");
		}
	}
} 