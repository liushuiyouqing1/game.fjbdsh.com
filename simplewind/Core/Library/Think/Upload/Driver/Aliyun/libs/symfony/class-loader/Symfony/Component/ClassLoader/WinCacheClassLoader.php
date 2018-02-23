<?php
namespace Symfony\Component\ClassLoader;
class WinCacheClassLoader
{
	private $prefix;
	protected $decorated;

	public function __construct($prefix, $decorated)
	{
		if (!extension_loaded('wincache')) {
			throw new \RuntimeException('Unable to use WinCacheClassLoader as WinCache is not enabled.');
		}
		if (!method_exists($decorated, 'findFile')) {
			throw new \InvalidArgumentException('The class finder must implement a "findFile" method.');
		}
		$this->prefix = $prefix;
		$this->decorated = $decorated;
	}

	public function register($prepend = false)
	{
		spl_autoload_register(array($this, 'loadClass'), true, $prepend);
	}

	public function unregister()
	{
		spl_autoload_unregister(array($this, 'loadClass'));
	}

	public function loadClass($class)
	{
		if ($file = $this->findFile($class)) {
			require $file;
			return true;
		}
	}

	public function findFile($class)
	{
		if (false === $file = wincache_ucache_get($this->prefix . $class)) {
			wincache_ucache_set($this->prefix . $class, $file = $this->decorated->findFile($class), 0);
		}
		return $file;
	}

	public function __call($method, $args)
	{
		return call_user_func_array(array($this->decorated, $method), $args);
	}
} 