<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilter;

class Number extends AttributeFilter
{
	/**
	* Constructor
	*
	* @return void
	*/
	public function __construct()
	{
		parent::__construct('s9e\\TextFormatter\\Parser\\BuiltInFilters::filterNumber');

		$this->addParameterByName('attrValue');
		$this->setJS('BuiltInFilters.filterNumber');
	}

	/**
	* {@inheritdoc}
	*/
	public function isSafeInCSS()
	{
		return true;
	}

	/**
	* {@inheritdoc}
	*/
	public function isSafeInJS()
	{
		return true;
	}

	/**
	* {@inheritdoc}
	*/
	public function isSafeInURL()
	{
		return true;
	}
}