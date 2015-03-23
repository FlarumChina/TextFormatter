<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

use DOMDocument;
use s9e\TextFormatter\Configurator\TemplateNormalizations\Custom;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\Custom
*/
class CustomTest extends Test
{
	/**
	* @testdox Constructor expects a valid callback
	* @expectedException PHPUnit_Framework_Error
	* @expectedExceptionMessage callable, string given
	*/
	public function testInvalidCallback()
	{
		if (version_compare(PHP_VERSION, '6', '>='))
		{
			$this->markTestSkipped('Triggers a fatal error on PHP 7');
		}
		new Custom('*invalid*');
	}

	/**
	* @testdox normalize() calls the user-defined callback with a DOMElement as argument
	*/
	public function testNormalize()
	{
		$dom = new DOMDocument;
		$dom->loadXML('<x/>');

		$mock = $this->getMock('stdClass', ['foo']);
		$mock->expects($this->once())
		     ->method('foo')
		     ->with($dom->documentElement);

		$normalizer = new Custom([$mock, 'foo']);
		$normalizer->normalize($dom->documentElement);
	}
}