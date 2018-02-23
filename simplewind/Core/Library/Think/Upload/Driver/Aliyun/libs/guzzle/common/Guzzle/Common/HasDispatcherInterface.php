<?php
namespace Guzzle\Common;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface HasDispatcherInterface
{
	public static function getAllEvents();

	public function setEventDispatcher(EventDispatcherInterface $eventDispatcher);

	public function getEventDispatcher();

	public function dispatch($eventName, array $context = array());

	public function addSubscriber(EventSubscriberInterface $subscriber);
} 