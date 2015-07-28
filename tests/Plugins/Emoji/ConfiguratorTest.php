<?php

namespace s9e\TextFormatter\Tests\Plugins\Emoji;

use s9e\TextFormatter\Plugins\Emoji\Configurator;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Emoji\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Automatically creates an "EMOJI" tag
	*/
	public function testCreatesTag()
	{
		$this->configurator->plugins->load('Emoji');
		$this->assertTrue($this->configurator->tags->exists('EMOJI'));
	}

	/**
	* @testdox Does not attempt to create a tag if it already exists
	*/
	public function testDoesNotCreateTag()
	{
		$tag = $this->configurator->tags->add('EMOJI');
		$this->configurator->plugins->load('Emoji');

		$this->assertSame($tag, $this->configurator->tags->get('EMOJI'));
	}

	/**
	* @testdox The name of the tag used can be changed through the "tagName" constructor option
	*/
	public function testCustomTagName()
	{
		$this->configurator->plugins->load('Emoji', ['tagName' => 'FOO']);
		$this->assertTrue($this->configurator->tags->exists('FOO'));
	}

	/**
	* @testdox The name of the attribute used can be changed through the "attrName" constructor option
	*/
	public function testCustomAttrName()
	{
		$this->configurator->plugins->load('Emoji', ['attrName' => 'bar']);
		$this->assertTrue($this->configurator->tags['EMOJI']->attributes->exists('bar'));
	}

	/**
	* @testdox The config array contains the name of the tag
	*/
	public function testConfigTagName()
	{
		$plugin = $this->configurator->plugins->load('Emoji', ['tagName' => 'FOO']);

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('tagName', $config);
		$this->assertSame('FOO', $config['tagName']);
	}

	/**
	* @testdox The config array contains the name of the attribute
	*/
	public function testConfigAttrName()
	{
		$plugin = $this->configurator->plugins->load('Emoji', ['attrName' => 'bar']);

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('attrName', $config);
		$this->assertSame('bar', $config['attrName']);
	}

	/**
	* @testdox The config array contains no entry for aliases if there are none
	*/
	public function testConfigNoAliases()
	{
		$plugin = $this->configurator->Emoji;
		$config = $plugin->asConfig();

		$this->assertArrayNotHasKey('aliases',           $config);
		$this->assertArrayNotHasKey('aliasesQuickMatch', $config);
		$this->assertArrayNotHasKey('aliasesRegexp',     $config);
	}

	/**
	* @testdox The config array contains aliases if applicable
	*/
	public function testConfigAliases()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->addAlias(':)', "\xF0\x9F\x98\x80");

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('aliases', $config);
		$this->assertSame([':)' => "\xF0\x9F\x98\x80"], $config['aliases']);
	}

	/**
	* @testdox The config array contains a regexp for aliases if applicable
	*/
	public function testConfigAliasesRegexp()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->addAlias(':D', "\xF0\x9F\x98\x80");

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('aliasesRegexp', $config);
		$this->assertEquals('/:D/', $config['aliasesRegexp']);
	}

	/**
	* @testdox The config array contains a quickMatch for aliases if applicable
	*/
	public function testConfigAliasesQuickMatch()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->addAlias(':D', "\xF0\x9F\x98\x80");

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('aliasesQuickMatch', $config);
		$this->assertEquals(':D', $config['aliasesQuickMatch']);
	}

	/**
	* @testdox The config array does not contain a quickMatch for aliases if impossible
	*/
	public function testConfigAliasesNoQuickMatch()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->addAlias(':D', "\xF0\x9F\x98\x80");
		$plugin->addAlias(';)', "\xF0\x9F\x98\x80");

		$config = $plugin->asConfig();

		$this->assertArrayNotHasKey('aliasesQuickMatch', $config);
	}

	/**
	* @testdox Can use the EmojiOne set
	*/
	public function testTemplateEmojiOne()
	{
		$this->configurator->Emoji->useEmojiOne();
		$this->assertContains('emojione', (string) $this->configurator->tags['EMOJI']->template);
	}

	/**
	* @testdox Can use the Twemoji set
	*/
	public function testTemplateTwemoji()
	{
		$this->configurator->Emoji->useTwemoji();
		$this->assertContains('twemoji', (string) $this->configurator->tags['EMOJI']->template);
	}

	/**
	* @testdox Uses Twemoji by default
	*/
	public function testDefaultTemplateTwemoji()
	{
		$this->configurator->Emoji;
		$this->assertContains('twemoji', (string) $this->configurator->tags['EMOJI']->template);
	}

	/**
	* @testdox Twemoji set can use PNG images
	*/
	public function testTwemojiPNG()
	{
		$this->configurator->Emoji->usePNG();
		$this->assertContains('png', (string) $this->configurator->tags['EMOJI']->template);
	}

	/**
	* @testdox Twemoji set can use SVG images
	*/
	public function testTwemojiSVG()
	{
		$this->configurator->Emoji->useSVG();
		$this->assertContains('svg', (string) $this->configurator->tags['EMOJI']->template);
	}

	/**
	* @testdox Twemoji set uses PNG images by default
	*/
	public function testTwemojiDefaultPNG()
	{
		$this->configurator->Emoji;
		$this->assertContains('png', (string) $this->configurator->tags['EMOJI']->template);
	}

	/**
	* @testdox EmojiOne set can use PNG images
	*/
	public function testEmojiOnePNG()
	{
		$this->configurator->Emoji->usePNG();
		$this->assertContains('png', (string) $this->configurator->tags['EMOJI']->template);
	}

	/**
	* @testdox EmojiOne set can use SVG images
	*/
	public function testEmojiOneSVG()
	{
		$this->configurator->Emoji->useSVG();
		$this->assertContains('svg', (string) $this->configurator->tags['EMOJI']->template);
	}

	/**
	* @testdox EmojiOne set uses PNG by Default
	*/
	public function testEmojiOneDefaultPNG()
	{
		$this->configurator->Emoji;
		$this->assertContains('png', (string) $this->configurator->tags['EMOJI']->template);
	}

	/**
	* @testdox Image size is forced by default with Twemoji
	*/
	public function testTwemojiForceImageSizeDefault()
	{
		$this->configurator->Emoji->useTwemoji();
		$template = (string) $this->configurator->tags['EMOJI']->template;
		$this->assertContains('width="16"',  $template);
		$this->assertContains('height="16"', $template);
	}

	/**
	* @testdox Image size can be omitted with Twemoji
	*/
	public function testTwemojiOmitImageSizeDefault()
	{
		$this->configurator->Emoji->useTwemoji();
		$this->configurator->Emoji->omitImageSize();
		$template = (string) $this->configurator->tags['EMOJI']->template;
		$this->assertNotContains('width="',  $template);
		$this->assertNotContains('height="', $template);
	}

	/**
	* @testdox Image size is forced by default with EmojiOne
	*/
	public function testEmojiOneImageForceSizeDefault()
	{
		$this->configurator->Emoji->useEmojiOne();
		$template = (string) $this->configurator->tags['EMOJI']->template;
		$this->assertContains('width="16"',  $template);
		$this->assertContains('height="16"', $template);
	}

	/**
	* @testdox Image size can be omitted with EmojiOne
	*/
	public function testEmojiOneOmitImageSizeDefault()
	{
		$this->configurator->Emoji->useEmojiOne();
		$this->configurator->Emoji->omitImageSize();
		$template = (string) $this->configurator->tags['EMOJI']->template;
		$this->assertNotContains('width="',  $template);
		$this->assertNotContains('height="', $template);
	}

	/**
	* @testdox Twemoji uses 16x16 assets by default
	*/
	public function testTwemojiDefaultSize()
	{
		$this->configurator->Emoji->useTwemoji();
		$this->configurator->Emoji->forceImageSize();
		$this->assertContains('16x16', (string) $this->configurator->tags['EMOJI']->template);
	}

	/**
	* @testdox Twemoji uses 36x36 assets if the image size is 22
	*/
	public function testTwemojiSize22()
	{
		$this->configurator->Emoji->useTwemoji();
		$this->configurator->Emoji->forceImageSize();
		$this->configurator->Emoji->setImageSize(22);
		$this->assertContains('36x36', (string) $this->configurator->tags['EMOJI']->template);
	}

	/**
	* @testdox Twemoji uses 36x36 assets if the image size is 36
	*/
	public function testTwemojiSize36()
	{
		$this->configurator->Emoji->useTwemoji();
		$this->configurator->Emoji->forceImageSize();
		$this->configurator->Emoji->setImageSize(36);
		$this->assertContains('36x36', (string) $this->configurator->tags['EMOJI']->template);
	}

	/**
	* @testdox Twemoji uses 72x72 assets if the image size is 72
	*/
	public function testTwemojiSize72()
	{
		$this->configurator->Emoji->useTwemoji();
		$this->configurator->Emoji->forceImageSize();
		$this->configurator->Emoji->setImageSize(72);
		$this->assertContains('72x72', (string) $this->configurator->tags['EMOJI']->template);
	}

	/**
	* @testdox removeAlias() removes given alias
	*/
	public function testRemoveAlias()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->addAlias(':)', "\xF0\x9F\x98\x80");
		$plugin->addAlias(':D', "\xF0\x9F\x98\x80");
		$plugin->addAlias('XD', "\xF0\x9F\x98\x86");
		$plugin->removeAlias(':)');

		$this->assertEquals(
			[':D' => "\xF0\x9F\x98\x80", 'XD' => "\xF0\x9F\x98\x86"],
			$plugin->getAliases()
		);
	}
}