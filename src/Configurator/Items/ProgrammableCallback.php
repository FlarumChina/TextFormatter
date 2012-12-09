<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Items\CallbackPlaceholder;

class ProgrammableCallback implements ConfigProvider
{
	/**
	* @var callback
	*/
	protected $callback;

	/**
	* @var array List of params to be passed to the callback
	*/
	protected $params = array();

	/**
	* @var array Variables associated with this instance
	*/
	protected $vars = array();

	/**
	* @param callable $callback
	*/
	public function __construct($callback)
	{
		if (!is_callable($callback))
		{
			throw new InvalidArgumentException(__METHOD__ . '() expects a callback');
		}

		// Normalize ['foo', 'bar'] to 'foo::bar'
		if (is_array($callback) && is_string($callback[0]))
		{
			$callback = $callback[0] . '::' . $callback[1];
		}

		$this->callback = $callback;
	}

	/**
	* Add a parameter by value
	*
	* @param mixed $paramValue
	*/
	public function addParameterByValue($paramValue)
	{
		$this->params[] = $paramValue;
	}

	/**
	* Add a parameter by name
	*
	* The value will be dynamically generated by the caller
	*
	* @param mixed $paramName
	*/
	public function addParameterByName($paramName)
	{
		$this->params[$paramName] = null;
	}

	/**
	* @return mixed
	*/
	public function getCallback()
	{
		return $this->callback;
	}

	/**
	* @return array
	*/
	public function getVars()
	{
		return $this->vars;
	}

	/**
	* Set the Javascript source for this callback
	*
	* @param string $js
	*/
	public function setJavascript($js)
	{
		$this->js = $js;
	}

	/**
	* @param  array $vars
	*/
	public function setVars(array $vars)
	{
		$this->vars = $vars;
	}

	/**
	* Create an instance of this class based on an array
	*
	* @param  array  $arr
	* @return static
	*/
	public static function fromArray(array $arr)
	{
		$obj = new static($arr['callback']);

		if (isset($arr['params']))
		{
			foreach ($arr['params'] as $k => $v)
			{
				if (is_numeric($k))
				{
					$obj->addParameterByValue($v);
				}
				else
				{
					$obj->addParameterByName($k);
				}
			}
		}

		if (isset($arr['vars']))
		{
			$obj->setVars($arr['vars']);
		}

		if (isset($arr['js']))
		{
			$obj->setJavascript($arr['js']);
		}

		return $obj;
	}

	/**
	* @return array
	*/
	public function asConfig()
	{
		$config = array();

		if ($this->callback instanceof CallbackPlaceholder)
		{
			// Keep the vars if the callback is a placeholder
			$config['callback'] = $this->callback->asConfig();
			$config['vars']     = $this->vars;
		}
		else
		{
			$config['callback'] = $this->callback;
		}

		foreach ($this->params as $k => $v)
		{
			if (is_numeric($k))
			{
				// By value
				$config['params'][] = $v;
			}
			elseif (isset($this->vars[$k]))
			{
				// By name, but the value is readily available in $this->vars
				$config['params'][] = $this->vars[$k];
			}
			else
			{
				// By name
				$config['params'][$k] = null;
			}
		}

		return $config;
	}
}