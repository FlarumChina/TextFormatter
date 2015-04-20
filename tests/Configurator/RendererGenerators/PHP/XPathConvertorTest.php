<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators\PHP;

use Exception;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor
*/
class XPathConvertorTest extends Test
{
	/**
	* @dataProvider getConvertXPathTestsBasic
	* @testdox convertXPath() basic tests
	*/
	public function testConvertXPathBasic($original, $expected)
	{
		$convertor = new XPathConvertor;
		if ($expected instanceof Exception)
		{
			$this->setExpectedException(get_class($expected), $expected->getMessage());
		}
		$this->assertSame($expected, $convertor->convertXPath($original));
	}

	/**
	* @dataProvider getConvertXPathTestsAdvanced
	* @testdox convertXPath() advanced tests (PCRE >= 8.13)
	*/
	public function testConvertXPathAdvanced($original, $expected, $fallback)
	{
		if (version_compare(PCRE_VERSION, '8.13', '<'))
		{
			$this->markTestSkipped('This optimization requires PCRE 8.13 or newer');
		}
		$convertor = new XPathConvertor;
		$this->assertSame($expected, $convertor->convertXPath($original));
	}

	/**
	* @dataProvider getConvertXPathTestsAdvanced
	* @testdox convertXPath() advanced tests (PCRE < 8.13)
	*/
	public function testConvertXPathAdvancedFallback($original, $expected, $fallback)
	{
		$convertor = new XPathConvertor;
		$convertor->pcreVersion = '8.02 2010-03-19';
		$this->assertSame($fallback, $convertor->convertXPath($original));
	}

	/**
	* @dataProvider getConvertXPathTestsMbstring
	* @testdox convertXPath() mbstring tests
	*/
	public function testConvertXPathMbstring($original, $expected, $setup = null)
	{
		if (version_compare(PCRE_VERSION, '8.13', '<'))
		{
			$this->markTestSkipped('This optimization requires PCRE 8.13 or newer');
		}
		$convertor = new XPathConvertor;
		$convertor->useMultibyteStringFunctions = true;
		$this->assertSame($expected, $convertor->convertXPath($original));
	}

	/**
	* @dataProvider getConvertConditionTestsBasic
	* @testdox convertCondition() basic tests
	*/
	public function testConvertConditionBasic($original, $expected)
	{
		$convertor = new XPathConvertor;
		$this->assertSame($expected, $convertor->convertCondition($original));
	}

	/**
	* @dataProvider getConvertConditionTestsAdvanced
	* @testdox convertCondition() advanced tests (PCRE >= 8.13)
	*/
	public function testConvertConditionAdvanced($original, $expected, $fallback)
	{
		if (version_compare(PCRE_VERSION, '8.13', '<'))
		{
			$this->markTestSkipped('This optimization requires PCRE 8.13 or newer');
		}
		$convertor = new XPathConvertor;
		$this->assertSame($expected, $convertor->convertCondition($original));
	}

	/**
	* @dataProvider getConvertConditionTestsAdvanced
	* @testdox convertCondition() advanced tests (PCRE < 8.13)
	*/
	public function testConvertConditionFallback($original, $expected, $fallback)
	{
		$convertor = new XPathConvertor;
		$convertor->pcreVersion = '8.02 2010-03-19';
		$this->assertSame($fallback, $convertor->convertCondition($original));
	}

	public function getConvertXPathTestsBasic()
	{
		return [
			[
				'"',
				new RuntimeException('Unterminated string literal')
			],
			[
				'@bar',
				"\$node->getAttribute('bar')"
			],
			[
				'.',
				"\$node->textContent"
			],
			[
				'$foo',
				"\$this->params['foo']"
			],
			[
				"'foo'",
				"'foo'"
			],
			[
				'"foo"',
				"'foo'"
			],
			[
				'local-name()',
				'$node->localName'
			],
			[
				'name()',
				'$node->nodeName'
			],
			[
				'123',
				"'123'"
			],
			[
				'normalize-space(@bar)',
				"\$this->xpath->evaluate('normalize-space(@bar)',\$node)"
			],
			[
				'substring(.,1,2)',
				"\$this->xpath->evaluate('substring(.,1,2)',\$node)"
			],
			[
				'substring(.,0,2)',
				"\$this->xpath->evaluate('substring(.,0,2)',\$node)"
			],
			[
				'substring(.,@x,1)',
				"\$this->xpath->evaluate('substring(.,@x,1)',\$node)"
			],
			[
				'substring(.,1,@x)',
				"\$this->xpath->evaluate('substring(.,1,@x)',\$node)"
			],
			[
				'substring(.,2)',
				"\$this->xpath->evaluate('substring(.,2)',\$node)"
			],
			// Custom representations
			[
				"substring('songWw',6-5*boolean(@songid),5)",
				"(\$node->hasAttribute('songid')?'songW':'w')"
			],
			[
				'400-360*boolean(@songid)',
				"(\$node->hasAttribute('songid')?40:400)"
			],
		];
	}

	public function getConvertXPathTestsAdvanced()
	{
		return [
			[
				'string-length(@bar)',
				"strlen(preg_replace('(.)us','.',\$node->getAttribute('bar')))",
				"\$this->xpath->evaluate('string-length(@bar)',\$node)",
			],
			[
				'string-length()',
				"strlen(preg_replace('(.)us','.',\$node->textContent))",
				"\$this->xpath->evaluate('string-length()',\$node)"
			],
			[
				'translate(@bar,"abc","ABC")',
				"strtr(\$node->getAttribute('bar'),'abc','ABC')",
				"\$this->xpath->evaluate('translate(@bar,'.'\"abc\"'.','.'\"ABC\"'.')',\$node)",
			],
			[
				'translate(@bar,"abc","ABC")',
				"strtr(\$node->getAttribute('bar'),'abc','ABC')",
				"\$this->xpath->evaluate('translate(@bar,'.'\"abc\"'.','.'\"ABC\"'.')',\$node)"
			],
			[
				'translate(@bar,"éè","ÉÈ")',
				"strtr(\$node->getAttribute('bar'),['é'=>'É','è'=>'È'])",
				"\$this->xpath->evaluate('translate(@bar,'.'\"éè\"'.','.'\"ÉÈ\"'.')',\$node)"
			],
			[
				'translate(@bar,"ab","ABC")',
				"strtr(\$node->getAttribute('bar'),'ab','AB')",
				"\$this->xpath->evaluate('translate(@bar,'.'\"ab\"'.','.'\"ABC\"'.')',\$node)"
			],
			[
				'translate(@bar,"abcd","AB")',
				"strtr(\$node->getAttribute('bar'),['a'=>'A','b'=>'B','c'=>'','d'=>''])",
				"\$this->xpath->evaluate('translate(@bar,'.'\"abcd\"'.','.'\"AB\"'.')',\$node)"
			],
			[
				'translate(@bar,"abbd","ABCD")',
				"strtr(\$node->getAttribute('bar'),'abd','ABD')",
				"\$this->xpath->evaluate('translate(@bar,'.'\"abbd\"'.','.'\"ABCD\"'.')',\$node)"
			],
			// Math
			[
				'@foo + 12',
				"\$node->getAttribute('foo')+12",
				"\$this->xpath->evaluate('string(@foo + 12)',\$node)"
			],
			[
				'44 + $bar',
				"44+\$this->params['bar']",
				"\$this->xpath->evaluate('string(44 + '.\$this->getParamAsXPath('bar').')',\$node)"
			],
			[
				'@h * 3600 + @m * 60 + @s',
				"\$node->getAttribute('h')*3600+\$node->getAttribute('m')*60+\$node->getAttribute('s')",
				"\$this->xpath->evaluate('string(@h * 3600 + @m * 60 + @s)',\$node)"
			],
			[
				'@x div@y',
				"\$node->getAttribute('x')/\$node->getAttribute('y')",
				"\$this->xpath->evaluate('string(@x div@y)',\$node)"
			],
			// Constant math
			[
				'12+34',
				'46',
				"\$this->xpath->evaluate('string(12+34)',\$node)"
			],
			[
				'44-11',
				'33',
				"\$this->xpath->evaluate('string(44-11)',\$node)"
			],
			[
				'7*9',
				'63',
				"\$this->xpath->evaluate('string(7*9)',\$node)"
			],
			[
				'10div2',
				'5',
				"\$this->xpath->evaluate('string(10div2)',\$node)"
			],
			[
				'1div100000',
				'1/100000',
				"\$this->xpath->evaluate('string(1div100000)',\$node)"
			],
		];
	}

	public function getConvertXPathTestsMbstring()
	{
		return [
			[
				// NOTE: as per XPath specs, the length is adjusted to the negative position
				'substring(.,0,2)',
				"mb_substr(\$node->textContent,0,1,'utf-8')"
			],
			[
				'substring(.,1,2)',
				"mb_substr(\$node->textContent,0,2,'utf-8')"
			],
			[
				'substring(.,@x,1)',
				"mb_substr(\$node->textContent,max(0,\$node->getAttribute('x')-1),1,'utf-8')"
			],
			[
				'substring(.,1,@x)',
				"mb_substr(\$node->textContent,0,max(0,\$node->getAttribute('x')),'utf-8')"
			],
			[
				'substring(.,2)',
				"mb_substr(\$node->textContent,1,null,'utf-8')"
			],
			[
				'string-length()',
				"mb_strlen(\$node->textContent,'utf-8')"
			],
			[
				'string-length(@bar)',
				"mb_strlen(\$node->getAttribute('bar'),'utf-8')"
			],
		];
	}

	public function getConvertConditionTestsBasic()
	{
		return [
			[
				'@foo',
				"\$node->hasAttribute('foo')"
			],
			[
				'not(@foo)',
				"!\$node->hasAttribute('foo')"
			],
			[
				'$foo',
				"!empty(\$this->params['foo'])"
			],
			[
				'not($foo)',
				"empty(\$this->params['foo'])"
			],
		];
	}

	public function getConvertConditionTestsAdvanced()
	{
		return [
			[
				".='foo'",
				"\$node->textContent==='foo'",
				"\$this->xpath->evaluate('.='.'\'foo\'',\$node)"
			],
			[
				"@foo='foo'",
				"\$node->getAttribute('foo')==='foo'",
				"\$this->xpath->evaluate('@foo='.'\'foo\'',\$node)"
			],
			[
				".='fo\"o'",
				"\$node->textContent==='fo\"o'",
				"\$this->xpath->evaluate('.='.'\'fo\"o\'',\$node)"
			],
			[
				'.=\'"_"\'',
				'$node->textContent===\'"_"\'',
				"\$this->xpath->evaluate('.='.'\'\"_\"\'',\$node)"
			],
			[
				".='foo'or.='bar'",
				"\$node->textContent==='foo'||\$node->textContent==='bar'",
				"\$this->xpath->evaluate('.='.'\'foo\''.'or.='.'\'bar\'',\$node)"
			],
			[
				'.=3',
				"\$node->textContent==3",
				"\$this->xpath->evaluate('.=3',\$node)"
			],
			[
				'.=022',
				"\$node->textContent==22",
				"\$this->xpath->evaluate('.=022',\$node)"
			],
			[
				'044=.',
				"44==\$node->textContent",
				"\$this->xpath->evaluate('044=.',\$node)"
			],
			[
				'@foo != @bar',
				"\$node->getAttribute('foo')!==\$node->getAttribute('bar')",
				"\$this->xpath->evaluate('@foo != @bar',\$node)"
			],
			[
				'@foo = @bar or @baz',
				"\$node->getAttribute('foo')===\$node->getAttribute('bar')||\$node->hasAttribute('baz')",
				"\$this->xpath->evaluate('@foo = @bar or @baz',\$node)"
			],
			[
				'not(@foo) and @bar',
				"!\$node->hasAttribute('foo')&&\$node->hasAttribute('bar')",
				"\$this->xpath->evaluate('not(@foo) and @bar',\$node)"
			],
			[
				'not(@foo and @bar)',
				"!(\$node->hasAttribute('foo')&&\$node->hasAttribute('bar'))",
				"\$this->xpath->evaluate('not(@foo and @bar)',\$node)"
			],
			[
				".='x'or.='y'or.='z'",
				"\$node->textContent==='x'||\$node->textContent==='y'||\$node->textContent==='z'",
				"\$this->xpath->evaluate('.='.'\'x\''.'or.='.'\'y\''.'or.='.'\'z\'',\$node)"
			],
			[
				"contains(@foo,'x')",
				"(strpos(\$node->getAttribute('foo'),'x')!==false)",
				"\$this->xpath->evaluate('contains(@foo,'.'\'x\''.')',\$node)"
			],
			[
				" contains( @foo , 'x' ) ",
				"(strpos(\$node->getAttribute('foo'),'x')!==false)",
				"\$this->xpath->evaluate('contains( @foo , '.'\'x\''.' )',\$node)"
			],
			[
				"not(contains(@id, 'bar'))",
				"(strpos(\$node->getAttribute('id'),'bar')===false)",
				"\$this->xpath->evaluate('not(contains(@id, '.'\'bar\''.'))',\$node)"
			],
			[
				"starts-with(@foo,'bar')",
				"(strpos(\$node->getAttribute('foo'),'bar')===0)",
				"\$this->xpath->evaluate('starts-with(@foo,'.'\'bar\''.')',\$node)"
			],
			[
				'@foo and (@bar or @baz)',
				"\$node->hasAttribute('foo')&&(\$node->hasAttribute('bar')||\$node->hasAttribute('baz'))",
				"\$this->xpath->evaluate('@foo and (@bar or @baz)',\$node)"
			],
			[
				'(@a = @b) or (@b = @c)',
				"(\$node->getAttribute('a')===\$node->getAttribute('b'))||(\$node->getAttribute('b')===\$node->getAttribute('c'))",
				"\$this->xpath->evaluate('(@a = @b) or (@b = @c)',\$node)"
			],
			[
				'ancestor::foo',
				"\$this->xpath->evaluate('boolean(ancestor::foo)',\$node)",
				"\$this->xpath->evaluate('boolean(ancestor::foo)',\$node)",
			],
			// Custom representations
			[
				"contains('upperlowerdecim',substring(@type,1,5))",
				"strpos('upperlowerdecim',substr(\$node->getAttribute('type'),0,5))!==false",
				"strpos('upperlowerdecim',substr(\$node->getAttribute('type'),0,5))!==false",
			],
		];
	}

	/**
	* @testdox Covering test for resolveConstantMathExpression()
	* @expectedException LogicException
	*/
	public function testResolveConstantMathExpressionException()
	{
		$className = 's9e\\TextFormatter\\Configurator\\RendererGenerators\\PHP\\XPathConvertor';
		$method = new ReflectionMethod($className, 'resolveConstantMathExpression');
		$method->setAccessible(true);
		$method->invoke(new XPathConvertor, 1, '**', 2);
	}

	/**
	* @testdox Covering test for convertXPath()
	*/
	public function testConvertXPathUnsupported()
	{
		$convertor = new XPathConvertor;
		$method = new ReflectionProperty(get_class($convertor), 'regexp');
		$method->setAccessible(true);
		$method->setValue($convertor, '()');
		$this->assertSame(
			"\$this->xpath->evaluate('@foo=@bar',\$node)",
			$convertor->convertXPath('@foo=@bar')
		);
	}
}