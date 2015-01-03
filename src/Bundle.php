<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

/**
* @method Parser   getParser()
* @method Renderer getRenderer()
*/
abstract class Bundle
{
	/**
	* Reset the cached parser and renderer
	*
	* @return void
	*/
	public static function reset()
	{
		static::$parser   = null;
		static::$renderer = null;
	}

	/**
	* Parse given text using a singleton instance of the bundled Parser
	*
	* @param  string $text Original text
	* @return string       Intermediate representation
	*/
	public static function parse($text)
	{
		if (!isset(static::$parser))
		{
			static::$parser = static::getParser();
		}

		if (isset(static::$beforeParse))
		{
			$text = call_user_func(static::$beforeParse, $text);
		}

		$xml = static::$parser->parse($text);

		if (isset(static::$afterParse))
		{
			$xml = call_user_func(static::$afterParse, $xml);
		}

		return $xml;
	}

	/**
	* Render an intermediate representation using a singleton instance of the bundled Renderer
	*
	* @param  string $xml    Intermediate representation
	* @param  array  $params Stylesheet parameters
	* @return string         Rendered result
	*/
	public static function render($xml, array $params = [])
	{
		if (!isset(static::$renderer))
		{
			static::$renderer = static::getRenderer();
		}

		if ($params)
		{
			static::$renderer->setParameters($params);
		}

		if (isset(static::$beforeRender))
		{
			$xml = call_user_func(static::$beforeRender, $xml);
		}

		$output = static::$renderer->render($xml);

		if (isset(static::$afterRender))
		{
			$output = call_user_func(static::$afterRender, $output);
		}

		return $output;
	}

	/**
	* Render an array of intermediate representations using a singleton instance of the bundled Renderer
	*
	* @param  array $arr    Array of XML strings
	* @param  array $params Stylesheet parameters
	* @return array         Array of render results (same keys, same order)
	*/
	public static function renderMulti(array $arr, array $params = [])
	{
		if (!isset(static::$renderer))
		{
			static::$renderer = static::getRenderer();
		}

		if ($params)
		{
			static::$renderer->setParameters($params);
		}

		if (isset(static::$beforeRender))
		{
			foreach ($arr as &$xml)
			{
				$xml = call_user_func(static::$beforeRender, $xml);
			}
			unset($xml);
		}

		$arr = static::$renderer->renderMulti($arr);

		if (isset(static::$afterRender))
		{
			foreach ($arr as &$output)
			{
				$output = call_user_func(static::$afterRender, $output);
			}
			unset($output);
		}

		return $arr;
	}

	/**
	* Transform an intermediate representation back to its original form
	*
	* @param  string $xml Intermediate representation
	* @return string      Original text
	*/
	public static function unparse($xml)
	{
		if (isset(static::$beforeUnparse))
		{
			$xml = call_user_func(static::$beforeUnparse, $xml);
		}

		$text = Unparser::unparse($xml);

		if (isset(static::$afterUnparse))
		{
			$text = call_user_func(static::$afterUnparse, $text);
		}

		return $text;
	}
}