<?php
namespace Symfony\Component\Yaml\Tests;

use Symfony\Component\Yaml\Yaml;

class YamlTest extends \PHPUnit_Framework_TestCase
{
	public function testParseAndDump()
	{
		$data = array('lorem' => 'ipsum', 'dolor' => 'sit');
		$yml = Yaml::dump($data);
		$parsed = Yaml::parse($yml);
		$this->assertEquals($data, $parsed);
		$filename = __DIR__ . '/Fixtures/index.yml';
		$contents = file_get_contents($filename);
		$parsedByFilename = Yaml::parse($filename);
		$parsedByContents = Yaml::parse($contents);
		$this->assertEquals($parsedByFilename, $parsedByContents);
	}
} 