<?php
namespace Guzzle\Plugin\ErrorResponse;

use Guzzle\Service\Command\CommandInterface;
use Guzzle\Http\Message\Response;

interface ErrorResponseExceptionInterface
{
	public static function fromCommand(CommandInterface $command, Response $response);
} 