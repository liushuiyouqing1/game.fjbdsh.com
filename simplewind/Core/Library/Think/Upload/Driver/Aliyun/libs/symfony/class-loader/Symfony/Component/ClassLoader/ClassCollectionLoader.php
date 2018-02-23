<?php
namespace Symfony\Component\ClassLoader;
class ClassCollectionLoader
{
	private static $loaded;
	private static $seen;
	private static $useTokenizer = true;

	public static function load($classes, $cacheDir, $name, $autoReload, $adaptive = false, $extension = '.php')
	{
		if (isset(self::$loaded[$name])) {
			return;
		}
		self::$loaded[$name] = true;
		$declared = array_merge(get_declared_classes(), get_declared_interfaces());
		if (function_exists('get_declared_traits')) {
			$declared = array_merge($declared, get_declared_traits());
		}
		if ($adaptive) {
			$classes = array_diff($classes, $declared);
			$name = $name . '-' . substr(md5(implode('|', $classes)), 0, 5);
		}
		$classes = array_unique($classes);
		$cache = $cacheDir . '/' . $name . $extension;
		$reload = false;
		if ($autoReload) {
			$metadata = $cache . '.meta';
			if (!is_file($metadata) || !is_file($cache)) {
				$reload = true;
			} else {
				$time = filemtime($cache);
				$meta = unserialize(file_get_contents($metadata));
				sort($meta[1]);
				sort($classes);
				if ($meta[1] != $classes) {
					$reload = true;
				} else {
					foreach ($meta[0] as $resource) {
						if (!is_file($resource) || filemtime($resource) > $time) {
							$reload = true;
							break;
						}
					}
				}
			}
		}
		if (!$reload && is_file($cache)) {
			require_once $cache;
			return;
		}
		$files = array();
		$content = '';
		foreach (self::getOrderedClasses($classes) as $class) {
			if (in_array($class->getName(), $declared)) {
				continue;
			}
			$files[] = $class->getFileName();
			$c = preg_replace(array('/^\s*<\?php/', '/\?>\s*$/'), '', file_get_contents($class->getFileName()));
			if (!$class->inNamespace()) {
				$c = "\nnamespace\n{\n" . $c . "\n}\n";
			}
			$c = self::fixNamespaceDeclarations('<?php ' . $c);
			$c = preg_replace('/^\s*<\?php/', '', $c);
			$content .= $c;
		}
		if (!is_dir(dirname($cache))) {
			mkdir(dirname($cache), 0777, true);
		}
		self::writeCacheFile($cache, '<?php ' . $content);
		if ($autoReload) {
			self::writeCacheFile($metadata, serialize(array($files, $classes)));
		}
	}

	public static function fixNamespaceDeclarations($source)
	{
		if (!function_exists('token_get_all') || !self::$useTokenizer) {
			if (preg_match('/namespace(.*?)\s*;/', $source)) {
				$source = preg_replace('/namespace(.*?)\s*;/', "namespace$1\n{", $source) . "}\n";
			}
			return $source;
		}
		$rawChunk = '';
		$output = '';
		$inNamespace = false;
		$tokens = token_get_all($source);
		for (reset($tokens); false !== $token = current($tokens); next($tokens)) {
			if (is_string($token)) {
				$rawChunk .= $token;
			} elseif (in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) {
				continue;
			} elseif (T_NAMESPACE === $token[0]) {
				if ($inNamespace) {
					$rawChunk .= "}\n";
				}
				$rawChunk .= $token[1];
				while (($t = next($tokens)) && is_array($t) && in_array($t[0], array(T_WHITESPACE, T_NS_SEPARATOR, T_STRING))) {
					$rawChunk .= $t[1];
				}
				if ('{' === $t) {
					$inNamespace = false;
					prev($tokens);
				} else {
					$rawChunk = rtrim($rawChunk) . "\n{";
					$inNamespace = true;
				}
			} elseif (T_START_HEREDOC === $token[0]) {
				$output .= self::compressCode($rawChunk) . $token[1];
				do {
					$token = next($tokens);
					$output .= is_string($token) ? $token : $token[1];
				} while ($token[0] !== T_END_HEREDOC);
				$output .= "\n";
				$rawChunk = '';
			} elseif (T_CONSTANT_ENCAPSED_STRING === $token[0]) {
				$output .= self::compressCode($rawChunk) . $token[1];
				$rawChunk = '';
			} else {
				$rawChunk .= $token[1];
			}
		}
		if ($inNamespace) {
			$rawChunk .= "}\n";
		}
		return $output . self::compressCode($rawChunk);
	}

	public static function enableTokenizer($bool)
	{
		self::$useTokenizer = (Boolean)$bool;
	}

	private static function compressCode($code)
	{
		return preg_replace(array('/^\s+/m', '/\s+$/m', '/([\n\r]+ *[\n\r]+)+/', '/[ \t]+/'), array('', '', "\n", ' '), $code);
	}

	private static function writeCacheFile($file, $content)
	{
		$tmpFile = tempnam(dirname($file), basename($file));
		if (false !== @file_put_contents($tmpFile, $content) && @rename($tmpFile, $file)) {
			@chmod($file, 0666 & ~umask());
			return;
		}
		throw new \RuntimeException(sprintf('Failed to write cache file "%s".', $file));
	}

	private static function getOrderedClasses(array $classes)
	{
		$map = array();
		self::$seen = array();
		foreach ($classes as $class) {
			try {
				$reflectionClass = new \ReflectionClass($class);
			} catch (\ReflectionException $e) {
				throw new \InvalidArgumentException(sprintf('Unable to load class "%s"', $class));
			}
			$map = array_merge($map, self::getClassHierarchy($reflectionClass));
		}
		return $map;
	}

	private static function getClassHierarchy(\ReflectionClass $class)
	{
		if (isset(self::$seen[$class->getName()])) {
			return array();
		}
		self::$seen[$class->getName()] = true;
		$classes = array($class);
		$parent = $class;
		while (($parent = $parent->getParentClass()) && $parent->isUserDefined() && !isset(self::$seen[$parent->getName()])) {
			self::$seen[$parent->getName()] = true;
			array_unshift($classes, $parent);
		}
		$traits = array();
		if (function_exists('get_declared_traits')) {
			foreach ($classes as $c) {
				foreach (self::resolveDependencies(self::computeTraitDeps($c), $c) as $trait) {
					if ($trait !== $c) {
						$traits[] = $trait;
					}
				}
			}
		}
		return array_merge(self::getInterfaces($class), $traits, $classes);
	}

	private static function getInterfaces(\ReflectionClass $class)
	{
		$classes = array();
		foreach ($class->getInterfaces() as $interface) {
			$classes = array_merge($classes, self::getInterfaces($interface));
		}
		if ($class->isUserDefined() && $class->isInterface() && !isset(self::$seen[$class->getName()])) {
			self::$seen[$class->getName()] = true;
			$classes[] = $class;
		}
		return $classes;
	}

	private static function computeTraitDeps(\ReflectionClass $class)
	{
		$traits = $class->getTraits();
		$deps = array($class->getName() => $traits);
		while ($trait = array_pop($traits)) {
			if ($trait->isUserDefined() && !isset(self::$seen[$trait->getName()])) {
				self::$seen[$trait->getName()] = true;
				$traitDeps = $trait->getTraits();
				$deps[$trait->getName()] = $traitDeps;
				$traits = array_merge($traits, $traitDeps);
			}
		}
		return $deps;
	}

	private static function resolveDependencies(array $tree, $node, \ArrayObject $resolved = null, \ArrayObject $unresolved = null)
	{
		if (null === $resolved) {
			$resolved = new \ArrayObject();
		}
		if (null === $unresolved) {
			$unresolved = new \ArrayObject();
		}
		$nodeName = $node->getName();
		$unresolved[$nodeName] = $node;
		foreach ($tree[$nodeName] as $dependency) {
			if (!$resolved->offsetExists($dependency->getName())) {
				self::resolveDependencies($tree, $dependency, $resolved, $unresolved);
			}
		}
		$resolved[$nodeName] = $node;
		unset($unresolved[$nodeName]);
		return $resolved;
	}
} 