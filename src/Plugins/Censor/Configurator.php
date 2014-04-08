<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Censor;

use ArrayAccess;
use Countable;
use Iterator;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\RegExp;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

/**
* @method mixed   add(string $key)
* @method array   asConfig()
* @method void    clear()
* @method bool    contains(mixed $value)
* @method integer count()
* @method mixed   current()
* @method void    delete(string $key)
* @method bool    exists(string $key)
* @method mixed   get(string $key)
* @method mixed   indexOf(mixed $value)
* @method integer|string key()
* @method mixed   next()
* @method string  normalizeKey(string $key)
* @method mixed   normalizeValue(mixed $value)
* @method void    offsetExists()
* @method void    offsetGet()
* @method void    offsetSet()
* @method void    offsetUnset()
* @method string  onDuplicate(string $action)
* @method void    rewind()
* @method mixed   set(string $key)
* @method bool    valid()
*/
class Configurator extends ConfiguratorBase implements ArrayAccess, Countable, Iterator
{
	use CollectionProxy;

	/**
	* @var array List of whitelisted words as [word => true]
	*/
	protected $allowed = [];

	/**
	* @var string Name of attribute used for the replacement
	*/
	protected $attrName = 'with';

	/**
	* @var NormalizedCollection List of [word => replacement]
	*/
	protected $collection;

	/**
	* @var string Default string used to replace censored words
	*/
	protected $defaultReplacement = '****';

	/**
	* @var array Options passed to the RegexpBuilder
	*/
	protected $regexpOptions = [
		'caseInsensitive' => true,
		'specialChars'    => [
			'*' => '[\\pL\\pN]*',
			'?' => '.',
			' ' => '\\s*'
		]
	];

	/**
	* @var string Name of the tag used to mark censored words
	*/
	protected $tagName = 'CENSOR';

	/**
	* Plugin's setup
	*
	* Will initialize its collection and create the plugin's tag if it does not exist
	*/
	protected function setUp()
	{
		$this->collection = new NormalizedCollection;
		$this->collection->onDuplicate('replace');

		if (isset($this->configurator->tags[$this->tagName]))
		{
			return;
		}

		// Create a tag
		$tag = $this->configurator->tags->add($this->tagName);

		// Create the attribute and make it optional
		$tag->attributes->add($this->attrName)->required = false;

		// Ensure that censored content can't ever be used by other tags
		$tag->rules->ignoreTags();

		// Create a template that renders censored words either as their custom replacement or as
		// the default replacement
		$tag->template =
			'<xsl:choose>
				<xsl:when test="@' . $this->attrName . '">
					<xsl:value-of select="@' . htmlspecialchars($this->attrName) . '"/>
				</xsl:when>
				<xsl:otherwise>' . htmlspecialchars($this->defaultReplacement) . '</xsl:otherwise>
			</xsl:choose>';
	}

	/**
	* Add a word to the list of uncensored words
	*
	* @param  string $word Word to exclude from the censored list
	* @return void
	*/
	public function allow($word)
	{
		$this->allowed[$word] = true;
	}

	/**
	* Return an instance of s9e\TextFormatter\Plugins\Censor\Helper
	*
	* @return Helper
	*/
	public function getHelper()
	{
		$config = $this->asConfig();

		if ($config === false)
		{
			// Use a dummy config with a regexp that doesn't match anything
			$config = [
				'attrName' => $this->attrName,
				'regexp'   => '/(?!)/',
				'tagName'  => $this->tagName
			];
		}
		else
		{
			ConfigHelper::filterVariants($config);
		}

		return new Helper($config);
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$words = array_diff_key(iterator_to_array($this->collection), $this->allowed);

		if (empty($words))
		{
			return false;
		}

		// Create the config
		$config = [
			'attrName' => $this->attrName,
			'regexp'   => $this->getWordsRegexp(array_keys($words)),
			'tagName'  => $this->tagName
		];

		// Add custom replacements
		$replacementWords = [];
		foreach ($words as $word => $replacement)
		{
			if (isset($replacement) && $replacement !== $this->defaultReplacement)
			{
				$replacementWords[$replacement][] = $word;
			}
		}

		foreach ($replacementWords as $replacement => $words)
		{
			$regexp = '/^' . RegexpBuilder::fromList($words, $this->regexpOptions) . '$/Diu';

			// Create a regexp with a JavaScript variant for each group of words
			$variant = new Variant($regexp);

			$regexp = str_replace('[\\pL\\pN]', '[^\\s!-\\/:-?]', $regexp);
			$variant->set('JS', RegexpConvertor::toJS($regexp));

			$config['replacements'][] = [$variant, $replacement];
		}

		// Add the whitelist
		if ($this->allowed)
		{
			$config['allowed'] = $this->getWordsRegexp(array_keys($this->allowed));
		}

		return $config;
	}

	/**
	* Generate a regexp that matches the given list of words
	*
	* @param  array   $words List of words
	* @return Variant        Regexp in a Variant container, with a JS variant
	*/
	protected function getWordsRegexp(array $words)
	{
		$regexp = RegexpBuilder::fromList($words, $this->regexpOptions);

		// Force atomic grouping for performance. Theorically it could prevent some matches but in
		// practice it shouldn't happen
		$regexp = preg_replace('/(?<!\\\\)((?>\\\\\\\\)*)\\(\\?:/', '$1(?>', $regexp);

		// Create a variant for the return value
		$variant = new Variant('/(?<![\\pL\\pN])' . $regexp . '(?![\\pL\\pN])/iu');

		// JavaScript regexps don't support Unicode properties, so instead of Unicode letters
		// we'll accept any non-whitespace, non-common punctuation
		$regexp = str_replace('[\\pL\\pN]', '[^\\s!-\\/:-?]', $regexp);
		$variant->set('JS', new RegExp('(?:^|\\W)' . $regexp . '(?!\\w)', 'gi'));

		return $variant;
	}
}