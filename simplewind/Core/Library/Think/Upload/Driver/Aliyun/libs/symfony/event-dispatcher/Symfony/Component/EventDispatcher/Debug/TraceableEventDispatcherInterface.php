<?php
namespace Symfony\Component\EventDispatcher\Debug;
interface TraceableEventDispatcherInterface
{
	public function getCalledListeners();

	public function getNotCalledListeners();
} 