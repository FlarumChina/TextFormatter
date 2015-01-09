<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class RemoveComments extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);

		foreach ($xpath->query('//comment()') as $comment)
			$comment->parentNode->removeChild($comment);
	}
}