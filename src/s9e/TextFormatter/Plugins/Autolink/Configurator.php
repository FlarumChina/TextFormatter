<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Autolink;

use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Configurator extends ConfiguratorBase
{
	/**
	* @var string Name of attribute that stores the link's URL
	*/
	protected $attrName = 'url';

	/**
	* {@inheritdoc}
	*/
	protected $quickMatch = '://';

	/**
	* @var string Name of the tag used to represent links
	*/
	protected $tagName = 'URL';

	/**
	* Creates the tag used by this plugin
	*
	* @return void
	*/
	protected function setUp()
	{
		if (isset($this->configurator->tags[$this->tagName]))
		{
			return;
		}

		// Create a tag
		$tag = $this->configurator->tags->add($this->tagName);

		// Add an attribute using the default url filter
		$filter = $this->configurator->attributeFilters->get('#url');
		$tag->attributes->add($this->attrName)->filterChain->append($filter);

		// Set the default template
		$tag->template
			= '<a href="{@' . $this->attrName . '}"><xsl:apply-templates/></a>';
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$schemeRegexp
			= RegexpBuilder::fromList($this->configurator->urlConfig->getAllowedSchemes());

		return [
			'attrName'   => $this->attrName,
			'quickMatch' => $this->quickMatch,
			'regexp'     => '#' . $schemeRegexp . '://\\S(?>[^\\s\\[\\]]*(?>\\[\\w*\\])?)++#iS',
			'tagName'    => $this->tagName
		];
	}
}