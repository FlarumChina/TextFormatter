<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use Exception;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\XPathHelper
*/
class XPathHelperTest extends Test
{
	/**
	* @testdox export('foo') returns 'foo'
	*/
	public function testExportSingleQuotes()
	{
		$this->assertSame("'foo'", XPathHelper::export('foo'));
	}

	/**
	* @testdox export("d'oh") returns "d'oh"
	*/
	public function testExportDoubleQuotes()
	{
		$this->assertSame('"d\'oh"', XPathHelper::export("d'oh"));
	}

	/**
	* @testdox export("'\"") returns concat("'",'"')
	*/
	public function testExportBothQuotes1()
	{
		$this->assertSame("concat(\"'\",'\"')", XPathHelper::export("'\""));
	}

	/**
	* @testdox export('"\'') returns concat('"',"'")
	*/
	public function testExportBothQuotes2()
	{
		$this->assertSame("concat('\"',\"'\")", XPathHelper::export('"\''));
	}

	/**
	* @testdox getVariables() tests
	* @dataProvider getGetVariablesTests
	*/
	public function testGetVariables($expr, $expected)
	{
		if ($expected instanceof Exception)
		{
			$this->setExpectedException(get_class($expected), $expected->getMessage());
		}

		$this->assertSame($expected, XPathHelper::getVariables($expr));
	}

	public function getGetVariablesTests()
	{
		return [
			[
				'',
				[]
			],
			[
				'$foo or $baz',
				['baz', 'foo']
			],
			[
				'$foo or "$baz"',
				['foo']
			],
			[
				'"$foo or $baz"',
				[]
			],
		];
	}

	/**
	* @testdox minify() tests
	* @dataProvider getMinifyTests
	*/
	public function testMinify($original, $expected)
	{
		if ($expected instanceof Exception)
		{
			$this->setExpectedException(get_class($expected), $expected->getMessage());
		}

		$this->assertSame($expected, XPathHelper::minify($original));
	}

	public function getMinifyTests()
	{
		return [
			[
				'',
				''
			],
			[
				' @foo ',
				'@foo'
			],
			[
				'@ foo',
				'@foo'
			],
			[
				'concat(@foo, @bar, @baz)',
				'concat(@foo,@bar,@baz)'
			],
			[
				"concat(@foo, ' @bar ', @baz)",
				"concat(@foo,' @bar ',@baz)"
			],
			[
				'@foo = 2',
				'@foo=2'
			],
			[
				'substring(., 1 + string-length(st), string-length() - (string-length(st) + string-length(et)))',
				'substring(.,1+string-length(st),string-length()-(string-length(st)+string-length(et)))'
			],
			[
				'@foo - bar = 2',
				'@foo -bar=2'
			],
			[
				'@foo- - 1 = 2',
				'@foo- -1=2'
			],
			[
				' foo or _bar ',
				'foo or _bar'
			],
			[
				'foo = "bar',
				new RuntimeException("Cannot parse XPath expression 'foo = \"bar'")
			]
		];
	}
}