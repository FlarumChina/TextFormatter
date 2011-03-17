<?php

namespace s9e\Toolkit\TextFormatter\Tests;

include_once __DIR__ . '/../ConfigBuilder.php';
include_once __DIR__ . '/../Parser.php';
include_once __DIR__ . '/../Renderer.php';

abstract class Test extends \PHPUnit_Framework_TestCase
{
	public function assertArrayMatches(array $expected, array $actual)
	{
		$this->reduceAndSortArrays($expected, $actual);
		$this->assertSame($expected, $actual);
	}

	protected function reduceAndSortArrays(array &$expected, array &$actual)
	{
		ksort($expected);
		ksort($actual);

		$actual = array_intersect_key($actual, $expected);

		foreach ($actual as $k => &$v)
		{
			if (is_array($expected[$k]) && is_array($v))
			{
				$this->reduceAndSortArrays($expected[$k], $v);
			}
		}
	}
}