<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use DOMElement;
use s9e\TextFormatter\Configurator\Items\Tag;

/*
* @codeCoverageIgnore
*/
abstract class TemplateCheck
{
	/*
	* Check a template for infractions to this check and throw any relevant Exception
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag     $tag      Tag this template belongs to
	* @return void
	*/
	abstract public function check(DOMElement $template, Tag $tag);
}