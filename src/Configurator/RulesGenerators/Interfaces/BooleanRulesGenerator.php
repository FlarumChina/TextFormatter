<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators\Interfaces;

use s9e\TextFormatter\Configurator\Helpers\TemplateInspector;

interface BooleanRulesGenerator
{
	/**
	* Generate boolean rules that apply to given template inspector
	*
	* @param  TemplateInspector $src Source template inspector
	* @return array                  Array of boolean rules as [ruleName => bool]
	*/
	public function generateBooleanRules(TemplateInspector $src);
}