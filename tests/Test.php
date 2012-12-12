<?php

namespace s9e\TextFormatter\Tests;

use DOMDocument;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use stdClass;
use XSLTProcessor;

use s9e\TextFormatter\Callback;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\JSParserGenerator;

abstract class Test extends \PHPUnit_Framework_TestCase
{
	public function __get($k)
	{
		switch ($k)
		{
			case 'configurator':
				return $this->configurator = new Configurator;

			case 'parser':
				return $this->parser = $this->configurator->getParser();

			case 'renderer':
				return $this->renderer = $this->configurator->getRenderer();

			case 'jspg':
				return $this->jspg = new JSParserGenerator($this->configurator);

			default:
				throw new RuntimeException("Bad __get('$k')");
		}
	}

	protected function assertArrayMatches(array $expected, array $actual, $removeNull = true)
	{
		$this->reduceAndSortArrays($expected, $actual, $removeNull);
		$this->assertSame($expected, $actual);
	}

	protected function reduceAndSortArrays(array &$expected, array &$actual, $removeNull = true)
	{
		if (empty($expected))
		{
			return;
		}

		ksort($expected);
		ksort($actual);

		$actual = array_intersect_key($actual, $expected);

		foreach ($actual as $k => &$v)
		{
			if (is_array($expected[$k]) && is_array($v))
			{
				$this->reduceAndSortArrays($expected[$k], $v, $removeNull);
			}
		}

		/**
		* Remove null values from $expected, they indicate that the key should NOT appear in $actual
		*/
		if ($removeNull)
		{
			foreach (array_keys($expected, null, true) as $k)
			{
				unset($expected[$k]);
			}
		}
	}

	protected function assertParsing($text, $expectedXml, $expectedLog = array('error' => null))
	{
		$actualXml = $this->parser->parse($text);
		$actualLog = $this->parser->getLog();

		if (!isset($expectedLog['debug']))
		{
			unset($actualLog['debug']);
		}

		$this->assertXmlStringEqualsXmlString($expectedXml, $actualXml);
		$this->assertArrayMatches($expectedLog, $actualLog);

		$this->assertReversible($text, $actualXml);
		$this->assertParserIsInACleanState();
	}

	protected function assertRendering($text, $expectedHtml, $expectedLog = array('error' => null))
	{
		$actualXml = $this->parser->parse($text);
		$actualLog = $this->parser->getLog();

		$this->assertArrayMatches($expectedLog, $actualLog);

		$actualHtml = $this->renderer->render($actualXml);
		$this->assertSame($expectedHtml, $actualHtml);

		$this->assertReversible($text, $actualXml);
		$this->assertParserIsInACleanState();
	}

	protected function assertTransformation($text, $expectedXml, $expectedHtml, $expectedLog = array('error' => null))
	{
		$actualXml = $this->parser->parse($text);
		$actualLog = $this->parser->getLog();

		$this->assertArrayMatches($expectedLog, $actualLog);
		$this->assertXmlStringEqualsXmlString($expectedXml, $actualXml);

		$actualHtml = $this->renderer->render($actualXml);
		$this->assertSame($expectedHtml, $actualHtml);

		$this->assertReversible($text, $actualXml);
		$this->assertParserIsInACleanState();
	}

	protected function assertReversible($text, $actualXml)
	{
		$this->assertSame(
			$text,
			html_entity_decode(strip_tags($actualXml)),
			'Could not revert to plain text'
		);
	}

	protected function assertParserIsInACleanState()
	{
		$r = new ReflectionClass($this->parser);

		$propNames = array(
			'unprocessedTags',
			'openTags',
			'openStartTags',
			'cntOpen'
		);

		foreach ($propNames as $propName)
		{
			$p = $r->getProperty($propName);
			$p->setAccessible(true);

			$this->assertSame(
				array(),
				array_filter($p->getValue($this->parser)),
				'The parser did not end up in a clean state: ' . $propName . ' is not empty'
			);
		}
	}

	protected function call($class, $methodName, array $args = array())
	{
		$r = new ReflectionMethod($class, $methodName);
		$r->setAccessible(true);

		return $r->invokeArgs((is_object($class) ? $class : null), $args);
	}

	protected function newCallback($callback)
	{
		return new Callback($callback);
	}

	protected function renderSnippet($xml, $xsl)
	{
		$xsl = '<?xml version="1.0" encoding="utf-8"?><xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"><xsl:output method="html" encoding="utf-8"	/>' . $xsl . '</xsl:stylesheet>';

		$dom = new DOMDocument;
		$dom->loadXML($xsl);

		$xslt = new XSLTProcessor;
		$xslt->importStylesheet($dom);

		$dom = new DOMDocument;
		$dom->loadXML($xml);

		return substr($xslt->transformToXml($dom), 0, -1);
	}
}