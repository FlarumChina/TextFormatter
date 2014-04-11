<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Regexp;

class HostnameList extends NormalizedList
{
	/**
	* Return this hostname list as a regexp's config
	*
	* @return \s9e\TextFormatter\Configurator\Items\Variant|null An instance of Variant that represents a regexp, or NULL if the collection is empty
	*/
	public function asConfig()
	{
		if (empty($this->items))
		{
			return null;
		}

		$regexp = new Regexp($this->getRegexp());

		return $regexp->asConfig();
	}

	/**
	* Return a regexp that matches the list of hostnames
	*
	* @return string
	*/
	public function getRegexp()
	{
		$hosts = [];
		foreach ($this->items as $host)
		{
			$hosts[] = $this->normalizeHostmask($host);
		}

		$regexp = RegexpBuilder::fromList(
			$hosts,
			[
				// Asterisks * are turned into a catch-all expression, while ^ and $ are preserved
				'specialChars' => [
					'*' => '.*',
					'^' => '^',
					'$' => '$'
				]
			]
		);

		return '/' . $regexp . '/DSis';
	}

	/**
	* Normalize a hostmask to a regular expression
	*
	* @param  string $host Hostname or hostmask
	* @return string
	*/
	protected function normalizeHostmask($host)
	{
		if (preg_match('#[\\x80-\xff]#', $host) && function_exists('idn_to_ascii'))
		{
			$host = idn_to_ascii($host);
		}

		if (substr($host, 0, 1) === '*')
		{
			// *.example.com => /\.example\.com$/
			$host = ltrim($host, '*');
		}
		else
		{
			// example.com => /^example\.com$/
			$host = '^' . $host;
		}

		if (substr($host, -1) === '*')
		{
			// example.* => /^example\./
			$host = rtrim($host, '*');
		}
		else
		{
			// example.com => /^example\.com$/
			$host .= '$';
		}

		return $host;
	}
}