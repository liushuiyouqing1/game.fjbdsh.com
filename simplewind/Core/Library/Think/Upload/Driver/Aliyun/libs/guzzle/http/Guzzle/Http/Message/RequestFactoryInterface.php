<?php
namespace Guzzle\Http\Message;

use Guzzle\Common\Collection;
use Guzzle\Http\EntityBodyInterface;
use Guzzle\Http\Url;

interface RequestFactoryInterface
{
	const OPTIONS_NONE = 0;
	const OPTIONS_AS_DEFAULTS = 1;

	public function fromMessage($message);

	public function fromParts($method, array $urlParts, $headers = null, $body = null, $protocol = 'HTTP', $protocolVersion = '1.1');

	public function create($method, $url, $headers = null, $body = null, array $options = array());

	public function applyOptions(RequestInterface $request, array $options = array(), $flags = self::OPTIONS_NONE);
} 