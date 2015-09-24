<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator;

use s9e\TextFormatter\Configurator\Helpers\AVTHelper;

abstract class TemplateGenerator
{
	/**
	* @var array Attributes used to generate current template
	*/
	protected $attributes;

	/**
	* @var array Default attributes
	*/
	protected $defaultAttributes = [
		'height'         => 360,
		'padding-height' => 0,
		'style'          => [],
		'width'          => 640
	];

	/**
	* Build the template representing the embedded content
	*
	* @return string
	*/
	abstract protected function getContentTemplate();

	/**
	* Build a template based on a list of attributes
	*
	* @param  array  $attributes
	* @return string
	*/
	public function getTemplate(array $attributes)
	{
		$this->attributes = $attributes + $this->defaultAttributes;

		$prepend = $append = '';
		if ($this->needsWrapper())
		{
			$this->attributes['style']['width']    = '100%';
			$this->attributes['style']['height']   = '100%';
			$this->attributes['style']['position'] = 'absolute';

			$outerStyle = 'display:inline-block;width:100%;max-width:' . $this->attributes['width'] . 'px';
			$innerStyle = 'position:relative;' . $this->getResponsivePadding();

			$prepend .= '<div>' . $this->generateAttributes(['style' => $outerStyle]);
			$prepend .= '<div>' . $this->generateAttributes(['style' => $innerStyle]);
			$append  .= '</div></div>';
		}
		else
		{
			$this->attributes['style']['width']  = '100%';
			$this->attributes['style']['height'] = $this->attributes['height'] . 'px';

			if (isset($this->attributes['max-width']))
			{
				$this->attributes['style']['max-width'] = $this->attributes['max-width'] . 'px';
			}
			elseif ($this->attributes['width'] !== '100%')
			{
				$this->attributes['style']['max-width'] = $this->attributes['width'] . 'px';
			}
		}

		return $prepend . $this->getContentTemplate() . $append;
	}

	/**
	* Format an attribute value to be used in an XPath expression
	*
	* @param  string $expr Original value
	* @return string       Formatted value
	*/
	protected function expr($expr)
	{
		$expr = trim($expr, '{}');

		return (preg_match('(^[@$]?[-\\w]+$)D', $expr)) ? $expr : "($expr)";
	}

	/**
	* Generate and return the padding declaration used in the responsive wrapper
	*
	* @return string
	*/
	protected function getResponsivePadding()
	{
		$height        = $this->expr($this->attributes['height']);
		$paddingHeight = $this->expr($this->attributes['padding-height']);
		$width         = $this->expr($this->attributes['width']);

		// Create the padding declaration for the fixed ratio
		$css = 'padding-bottom:<xsl:value-of select="100*(' . $height . '+' . $paddingHeight . ')div' . $width . '"/>%';
		
		// Add the padding declaration for the computed ratio if applicable
		if (!empty($this->attributes['padding-height']))
		{
			// NOTE: there needs to be whitespace around tokens in calc()
			$css .= ';padding-bottom:calc(<xsl:value-of select="100*' . $height . ' div' . $width . '"/>% + ' . $paddingHeight . 'px)';
		}

		return $css;
	}

	/**
	* Generate xsl:attributes elements from an array
	*
	* @param  array  $attributes Array of [name => value] where value can be XSL code
	* @return string             XSL source
	*/
	protected function generateAttributes(array $attributes)
	{
		if (isset($attributes['style']) && is_array($attributes['style']))
		{
			$attributes['style'] = $this->generateStyle($attributes['style']);
		}

		ksort($attributes);
		$xsl = '';
		foreach ($attributes as $attrName => $attrValue)
		{
			$innerXML = (strpos($attrValue, '<xsl:') !== false) ? $attrValue : AVTHelper::toXSL($attrValue);

			$xsl .= '<xsl:attribute name="' . htmlspecialchars($attrName, ENT_QUOTES, 'UTF-8') . '">' . $innerXML . '</xsl:attribute>';
		}

		return $xsl;
	}

	/**
	* Generate a CSS declaration based on an array of CSS properties
	*
	* @param  array  $properties Property name => property value
	* @return string
	*/
	protected function generateStyle(array $properties)
	{
		ksort($properties);

		$style = '';
		foreach ($properties as $name => $value)
		{
			$style .= $name . ':' . $value . ';';
		}

		return trim($style, ';');
	}

	/**
	* Merge two array of attributes
	*
	* @param  array $defaultAttributes
	* @param  array $newAttributes
	* @return array
	*/
	protected function mergeAttributes(array $defaultAttributes, array $newAttributes)
	{
		$attributes = array_merge($defaultAttributes, $newAttributes);
		if (isset($defaultAttributes['style'], $newAttributes['style']))
		{
			// Re-add the default attributes that were lost (but not replaced) in the merge
			$attributes['style'] += $defaultAttributes['style'];
		}

		return $attributes;
	}

	/**
	* Test whether current template needs a wrapper to be responsive
	*
	* @return bool
	*/
	protected function needsWrapper()
	{
		if ($this->attributes['width'] === '100%')
		{
			return false;
		}

		if (isset($this->attributes['onload']) && strpos($this->attributes['onload'], '.height') !== false)
		{
			return false;
		}

		return true;
	}
}