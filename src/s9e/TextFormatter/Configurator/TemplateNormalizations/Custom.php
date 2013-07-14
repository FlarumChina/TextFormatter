<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMException;
use DOMNode;
use DOMText;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class Custom extends TemplateNormalization
{
	/**
	* @var callback Normalization callback
	*/
	protected $callback;

	/**
	* Constructor
	*
	* @param  callback $callback Normalization callback
	* @return void
	*/
	public function __construct(callable $callback)
	{
		$this->callback = $callback;
	}

	/**
	* Call the user-supplied callback
	*
	* @param  DOMNode $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMNode $template)
	{
		call_user_func($this->callback, $template);
	}
}