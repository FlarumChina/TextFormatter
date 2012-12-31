<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

class PluginCollection extends NormalizedCollection
{
	/**
	* @var Configurator
	*/
	protected $configurator;

	/**
	* Constructor
	*
	* @param Configurator $configurator
	*/
	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
	}

	/**
	* Validate a plugin name
	*
	* @param  string $pluginName
	* @return string
	*/
	public function normalizeKey($pluginName)
	{
		if (!preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $pluginName))
		{
			throw new InvalidArgumentException("Invalid plugin name '" . $pluginName . "'");
		}

		return $pluginName;
	}

	/**
	* Create a plugin instance/ensure it implements the correct interface
	*
	* @param  mixed Either a class name or an object that implements ConfiguratorBase
	* @return void
	*/
	public function normalizeValue($value)
	{
		if (is_string($value) && class_exists($value))
		{
			$value = new $value($this->configurator);
		}

		if ($value instanceof ConfiguratorBase)
		{
			return $value;
		}

		throw new InvalidArgumentException('PluginCollection::normalizeValue() expects a class name or an object that implements s9e\\TextFormatter\\Plugins\\ConfiguratorBase');
	}

	/**
	* Load a default plugin
	*
	* @param  string $pluginName    Name of the plugin
	* @param  array  $overrideProps Properties of the plugin will be overwritten with those
	* @return ConfiguratorBase
	*/
	public function load($pluginName, array $overrideProps = array())
	{
		// Validate the plugin name / class
		$pluginName = $this->normalizeKey($pluginName);
		$className  = 's9e\\TextFormatter\\Plugins\\' . $pluginName . '\\Configurator';

		if (!class_exists($className))
		{
			throw new RuntimeException("Class '" . $className . "' does not exist");
		}

		// Create the plugin
		$plugin = new $className($this->configurator, $overrideProps);

		// Save it
		$this->set($pluginName, $plugin);

		// Return it
		return $plugin;
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$plugins = parent::asConfig();

		// Remove unused plugins
		$plugins = array_filter($plugins);

		// Adjust plugins' default properties
		foreach ($plugins as $pluginName => &$pluginConfig)
		{
			$plugin = $this->get($pluginName);

			// Add base properties
			$pluginConfig += $plugin->getBaseProperties();

			// Remove quickMatch if it's false
			if ($pluginConfig['quickMatch'] === false)
			{
				unset($pluginConfig['quickMatch']);
			}

			// Remove regexpLimit if there's no regexp
			if (!isset($pluginConfig['regexp']))
			{
				unset($pluginConfig['regexpLimit']);
				unset($pluginConfig['regexpLimitAction']);
			}

			if (!isset($pluginConfig['parser']))
			{
				$pluginConfig['parser'] = new Variant;
				$pluginConfig['parser']->setDynamic('Javascript', array($plugin, 'getJSParser'));
			}
		}
		unset($pluginConfig);

		return $plugins;
	}
}