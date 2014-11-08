<?php

namespace s9e\TextFormatter\Tests\Configurator\Items;

use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\ProgrammableCallback
*/
class ProgrammableCallbackTest extends Test
{
	/**
	* @testdox __construct() throws an InvalidArgumentException if its argument is not callable
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage s9e\TextFormatter\Configurator\Items\ProgrammableCallback::__construct() expects a callback
	*/
	public function testInvalidCallback()
	{
		new ProgrammableCallback('*invalid*');
	}

	/**
	* @testdox An array of variables can be set with setVars() or retrieved with getVars()
	*/
	public function testVars()
	{
		$vars = array('foo' => 'bar', 'baz' => 'quux');
		$pc   = new ProgrammableCallback(function($a,$b){});
		$pc->setVars($vars);

		$this->assertSame($vars, $pc->getVars());
	}

	/**
	* @testdox setVars() is chainable
	*/
	public function testSetVarsChainable()
	{
		$pc = new ProgrammableCallback('strtolower');

		$this->assertSame($pc, $pc->setVars(array('foo' => 'bar')));
	}

	/**
	* @testdox A single variable can be set with setVar() without overwriting other variables
	*/
	public function testSetVar()
	{
		$vars = array('foo' => 'bar', 'baz' => 'quux');
		$pc   = new ProgrammableCallback(function($a,$b){});
		$pc->setVars(array('foo' => 'bar'));
		$pc->setVar('baz', 'quux');

		$this->assertSame($vars, $pc->getVars());
	}

	/**
	* @testdox setVars() is chainable
	*/
	public function testSetVarChainable()
	{
		$pc = new ProgrammableCallback('strtolower');

		$this->assertSame($pc, $pc->setVar('foo', 'bar'));
	}

	/**
	* @testdox addParameterByValue() adds a parameter as a value with no name
	*/
	public function testAddParameterByValue()
	{
		$pc = new ProgrammableCallback('strtolower');
		$pc->addParameterByValue('foobar');

		$config = $pc->asConfig();

		$this->assertSame(
			array('foobar'),
			$config['params']
		);
	}

	/**
	* @testdox addParameterByValue() is chainable
	*/
	public function testAddParameterByValueChainable()
	{
		$pc = new ProgrammableCallback('strtolower');

		$this->assertSame($pc, $pc->addParameterByValue('foobar'));
	}

	/**
	* @testdox addParameterByName() adds a parameter as a name with no value
	*/
	public function testAddParameterByName()
	{
		$pc = new ProgrammableCallback('strtolower');
		$pc->addParameterByName('foobar');

		$config = $pc->asConfig();

		$this->assertSame(
			array('foobar' => null),
			$config['params']
		);
	}

	/**
	* @testdox addParameterByName() is chainable
	*/
	public function testAddParameterByNameChainable()
	{
		$pc = new ProgrammableCallback('strtolower');

		$this->assertSame($pc, $pc->addParameterByName('foobar'));
	}

	/**
	* @testdox resetParameters() removes all parameters
	*/
	public function testResetParameters()
	{
		$pc = new ProgrammableCallback('mt_rand');
		$pc->addParameterByValue(1);
		$pc->addParameterByValue(2);
		$pc->resetParameters();
		$pc->addParameterByValue(4);
		$pc->addParameterByValue(5);

		$config = $pc->asConfig();

		$this->assertSame(
			array(4, 5),
			$config['params']
		);
	}

	/**
	* @testdox resetParameters() is chainable
	*/
	public function testResetParametersChainable()
	{
		$pc = new ProgrammableCallback('strtolower');

		$this->assertSame($pc, $pc->resetParameters());
	}

	/**
	* @testdox Callback '\\strtotime' is normalized to 'strtotime'
	*/
	public function testNormalizeNamespace()
	{
		$pc     = new ProgrammableCallback('\\strtotime');
		$config = $pc->asConfig();

		$this->assertSame('strtotime', $config['callback']);
	}

	/**
	* @testdox Callback ['foo','bar'] is normalized to 'foo::bar'
	*/
	public function testNormalizeStatic()
	{
		$pc     = new ProgrammableCallback(array(__NAMESPACE__ . '\\DummyStaticCallback', 'bar'));
		$config = $pc->asConfig();

		$this->assertSame(__NAMESPACE__ . '\\DummyStaticCallback::bar', $config['callback']);
	}

	/**
	* @testdox Callback ['\\foo','bar'] is normalized to 'foo::bar'
	*/
	public function testNormalizeStaticNamespace()
	{
		$pc     = new ProgrammableCallback(array('\\' . __NAMESPACE__ . '\\DummyStaticCallback', 'bar'));
		$config = $pc->asConfig();

		$this->assertSame(__NAMESPACE__ . '\\DummyStaticCallback::bar', $config['callback']);
	}

	/**
	* @testdox getCallback() returns the callback
	*/
	public function testGetCallback()
	{
		$pc = new ProgrammableCallback('strtolower');

		$this->assertSame('strtolower', $pc->getCallback());
	}

	/**
	* @testdox getJS() returns NULL by default
	*/
	public function testGetJS()
	{
		$pc = new ProgrammableCallback(function(){});

		$this->assertNull($pc->getJS());
	}

	/**
	* @testdox getJS() returns an instance of Code if no JS was set and the callback is a function found in Configurator/JavaScript/functions/
	*/
	public function testGetJSAutofills()
	{
		$pc = new ProgrammableCallback('strtolower');
		$js = $pc->getJS();

		$this->assertInstanceOf('s9e\\TextFormatter\\Configurator\\JavaScript\\Code', $js);
		$this->assertStringEqualsFile(
			__DIR__ . '/../../../src/Configurator/JavaScript/functions/strtolower.js',
			(string) $js
		);
	}

	/**
	* @testdox getJS() returns NULL if no JS was set and the callback is a function that is not found in Configurator/JavaScript/functions/
	*/
	public function testGetJSNoAutofill()
	{
		$pc = new ProgrammableCallback('levenshtein');

		$this->assertNull($pc->getJS());
	}

	/**
	* @testdox setJS() accepts a string and normalizes it to an instance of Code
	*/
	public function testSetJSString()
	{
		$js = 'function(str){return str.toLowerCase();}';

		$pc = new ProgrammableCallback('strtolower');
		$pc->setJS($js);

		$this->assertEquals(new Code($js), $pc->getJS());
	}

	/**
	* @testdox setJS() accepts an instance of Code
	*/
	public function testSetJSInstance()
	{
		$js = new Code('function(str){return str.toLowerCase();}');

		$pc = new ProgrammableCallback('strtolower');
		$pc->setJS($js);

		$this->assertSame($js, $pc->getJS());
	}

	/**
	* @testdox setJS() is chainable
	*/
	public function testSetJSChainable()
	{
		$js = 'function(str){return str.toLowerCase();}';
		$pc = new ProgrammableCallback('strtolower');

		$this->assertSame($pc, $pc->setJS($js));
	}

	/**
	* @testdox asConfig() returns an array containing the callback
	*/
	public function testAsConfig()
	{
		$pc     = new ProgrammableCallback('mt_rand');
		$config = $pc->asConfig();

		$this->assertArrayHasKey('callback', $config);
		$this->assertSame('mt_rand', $config['callback']);
	}

	/**
	* @testdox asConfig() replaces the by-name parameters by the values stored in vars if available
	*/
	public function testAsConfigVars()
	{
		$pc = new ProgrammableCallback('mt_rand');
		$pc->addParameterByName('min');
		$pc->addParameterByValue(55);
		$pc->setVars(array('min' => 5));

		$config = $pc->asConfig();

		$this->assertSame(
			array(5, 55),
			$config['params']
		);
	}

	/**
	* @testdox asConfig() returns the callback's JavaScript as a variant if available
	*/
	public function testAsConfigJavaScript()
	{
		$js = new Code('function(){return "";}');

		$pc = new ProgrammableCallback(function(){});
		$pc->setJS($js);

		$config = $pc->asConfig();

		$this->assertArrayHasKey('js', $config);
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Variant',
			$config['js']
		);
		$this->assertSame($js, $config['js']->get('JS'));
	}

	/**
	* @testdox asConfig() uses getJS() to autofill the JavaScript variant
	*/
	public function testAsConfigJavaScriptAutofill()
	{
		$pc = new ProgrammableCallback('strtolower');

		$config = $pc->asConfig();

		$this->assertArrayHasKey('js', $config);
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Variant',
			$config['js']
		);
		$this->assertStringEqualsFile(
			__DIR__ . '/../../../src/Configurator/JavaScript/functions/strtolower.js',
			(string) $config['js']->get('JS')
		);
	}

	/**
	* @testdox asConfig() replaces values that implement ConfigProvider with their config value
	*/
	public function testAsConfigProvider()
	{
		$pc = new ProgrammableCallback(function(){});
		$pc->setVars(array('x' => new Regexp('/x/')));

		$pc->addParameterByName('x');
		$pc->addParameterByValue(new Regexp('/y/'));

		$config = $pc->asConfig();

		$this->assertNotInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Regexp',
			$config['params'][0]
		);

		$this->assertNotInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Regexp',
			$config['params'][1]
		);
	}

	/**
	* @testdox asConfig() recurses into params via ConfigHelper::toArray() to convert structures to arrays
	*/
	public function testAsConfigProviderDeep()
	{
		$pc = new ProgrammableCallback(function(){});
		$pc->addParameterByValue(array(new Regexp('/x/')));

		$config = $pc->asConfig();

		$this->assertNotInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Regexp',
			$config['params'][0][0]
		);
	}

	/**
	* @testdox asConfig() preserves NULL values and empty arrays in the callback's parameters
	*/
	public function testAsConfigPreserve()
	{
		$pc = new ProgrammableCallback(function(){});
		$pc->addParameterByValue(null);
		$pc->addParameterByValue(array());

		$config = $pc->asConfig();

		$this->assertSame(
			array(null, array()),
			$config['params']
		);
	}
}

class DummyStaticCallback
{
	public static function bar()
	{
	}
}