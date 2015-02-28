<?php

namespace s9e\TextFormatter\Tests\Renderers;

use s9e\TextFormatter\Tests\RendererTests;
use s9e\TextFormatter\Tests\Test;

/**
* @requires extension xsl
* @covers s9e\TextFormatter\Renderer
* @covers s9e\TextFormatter\Renderers\XSLT
*/
class XSLTTest extends Test
{
	use RendererTests;

	/**
	* @testdox Is serializable
	*/
	public function testSerializable()
	{
		$renderer = $this->configurator->getRenderer();

		$this->assertEquals(
			$renderer,
			unserialize(serialize($renderer))
		);
	}

	/**
	* @testdox Does not serialize the XSLTProcessor instance
	*/
	public function testSerializableNoProc()
	{
		$renderer = $this->configurator->getRenderer();
		$renderer->render('<r>..</r>');

		$this->assertNotContains(
			'XSLTProcessor',
			serialize($renderer)
		);
	}

	/**
	* @testdox Preserves other properties during serialization
	*/
	public function testSerializableCustomProps()
	{
		$renderer = $this->configurator->getRenderer();
		$renderer->foo = 'bar';

		$this->assertAttributeEquals(
			'bar',
			'foo',
			unserialize(serialize($renderer))
		);
	}

	/**
	* @testdox setParameter() accepts values that contain both types of quotes but replaces ASCII character " with Unicode character 0xFF02 because of https://bugs.php.net/64137
	*/
	public function testSetParameterBothQuotes()
	{
		$this->configurator->tags->add('X')->template = '<xsl:value-of select="$foo"/>';
		$this->configurator->rendering->parameters->add('foo');
		$renderer = $this->configurator->getRenderer();

		$values = [
			'"\'...\'"',
			'\'\'""...\'\'"\'"'
		];

		foreach ($values as $value)
		{
			$renderer->setParameter('foo', $value);
			$this->assertSame(
				str_replace('"', "\xEF\xBC\x82", $value),
				$renderer->render('<r><X/></r>')
			);
		}
	}

	/**
	* @testdox Does not output </embed> end tags
	*/
	public function testNoEmbedEndTag()
	{
		$this->configurator->tags->add('X')->template
			= '<object><embed src="foo"/></object>';

		$this->assertSame(
			'<object><embed src="foo"></object>',
			$this->configurator->getRenderer()->render('<r><X/></r>')
		);
	}
}