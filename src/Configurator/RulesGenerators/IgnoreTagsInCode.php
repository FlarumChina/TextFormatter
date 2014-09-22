<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;

use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;

class IgnoreTagsInCode implements BooleanRulesGenerator
{
	/*
	* {@inheritdoc}
	*/
	public function generateBooleanRules(TemplateForensics $src)
	{
		$xpath = new DOMXPath($src->getDOM());

		if ($xpath->evaluate('count(//code//xsl:apply-templates)'))
			return array('ignoreTags' => \true);

		return array();
	}
}