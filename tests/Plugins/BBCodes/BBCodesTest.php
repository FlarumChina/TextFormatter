<?php

namespace s9e\TextFormatter\Tests\Plugins\BBCodes;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\BBCodes\BBCodes;
use s9e\TextFormatter\Tests\Test;

/**
* @coversNothing
*/
class BBCodesTest extends Test
{
	/**
	* @testdox BBCodes from repository.xml render nicely
	* @dataProvider getPredefinedBBCodesTests
	*/
	public function test($original, $expected, $setup = null)
	{
		$configurator = new Configurator;

		if (isset($setup))
		{
			call_user_func($setup, $configurator);
		}

		// Capture the names of the BBCodes used
		preg_match_all('/\\[([*\\w]+)/', $original, $matches);

		foreach ($matches[1] as $bbcodeName)
		{
			if (!isset($configurator->BBCodes[$bbcodeName]))
			{
				$configurator->BBCodes->addFromRepository($bbcodeName);
			}
		}

		$configurator->addHTML5Rules();

		$xml  = $configurator->getParser()->parse($original);
		$html = $configurator->getRenderer()->render($xml);

		$this->assertSame(
			$expected,
			$html
		);
	}

	/**
	* @group needs-js
	* @testdox BBCodes from repository.xml are parsed identically by the JavaScript parser
	* @dataProvider getPredefinedBBCodesTests
	*/
	public function testJS($original, $expected, $setup = null)
	{
		$configurator = new Configurator;

		if (isset($setup))
		{
			call_user_func($setup, $configurator);
		}

		// Capture the names of the BBCodes used
		preg_match_all('/\\[([*\\w]+)/', $original, $matches);

		foreach ($matches[1] as $bbcodeName)
		{
			if (!isset($configurator->BBCodes[$bbcodeName]))
			{
				$configurator->BBCodes->addFromRepository($bbcodeName);
			}
		}

		$configurator->addHTML5Rules();

		$src = $configurator->javascript->getParser();

		$this->assertSame(
			$configurator->getParser()->parse($original),
			$this->execJS($src, $original)
		);
	}

	public function getPredefinedBBCodesTests()
	{
		return [
			[
				'[acronym="foobar"]F.B[/acronym]',
				'<acronym title="foobar">F.B</acronym>'
			],
			[
				'[acronym="\'\"foobar\"\'"]F.B[/acronym]',
				'<acronym title="\'&quot;foobar&quot;\'">F.B</acronym>'
			],
			[
				'[align=center]...[/align]',
				'<div style="text-align:center">...</div>'
			],
			[
				'[align=;color:red]...[/align]',
				'[align=;color:red]...[/align]'
			],
			[
				'x [b]bold[/b] y',
				'x <b>bold</b> y'
			],
			[
				'x [B]BOLD[/b] y',
				'x <b>BOLD</b> y'
			],
			[
				'x [background=yellow]color me[/background] y',
				'x <span style="background-color:yellow">color me</span> y'
			],
			[
				'x [C][b]not bold[/b][/C] y',
				'x <code class="inline">[b]not bold[/b]</code> y'
			],
			[
				'x [C:123][C][b]not bold[/b][/C][/C:123] y',
				'x <code class="inline">[C][b]not bold[/b][/C]</code> y'
			],
			[
				'[center]...[/center]',
				'<div style="text-align:center">...</div>'
			],
			[
				'[code]echo "Hello world";[/code]',
				'<pre><code class="">echo "Hello world";</code></pre><script>var l=document.createElement("link");l.type="text/css";l.rel="stylesheet";l.href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/7.3/styles/default.min.css";document.getElementsByTagName("head")[0].appendChild(l)</script><script onload="hljs.initHighlighting()" src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/7.3/highlight.min.js"></script>',
			],
			[
				'[code=html]<b>Hello world</b>[/code]',
				'<pre><code class="html">&lt;b&gt;Hello world&lt;/b&gt;</code></pre><script>var l=document.createElement("link");l.type="text/css";l.rel="stylesheet";l.href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/7.3/styles/default.min.css";document.getElementsByTagName("head")[0].appendChild(l)</script><script onload="hljs.initHighlighting()" src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/7.3/highlight.min.js"></script>',
			],
			[
				'[code]alert("first");[/code][code]alert("second");[/code]',
				'<pre><code class="">alert("first");</code></pre><pre><code class="">alert("second");</code></pre><script>var l=document.createElement("link");l.type="text/css";l.rel="stylesheet";l.href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/7.3/styles/default.min.css";document.getElementsByTagName("head")[0].appendChild(l)</script><script onload="hljs.initHighlighting()" src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/7.3/highlight.min.js"></script>'
			],
			[
				'[code=php]echo "Hello world";[/code]',
				'<pre><code class="php">echo "Hello world";</code></pre><script>var l=document.createElement("link");l.type="text/css";l.rel="stylesheet";l.href="highlight.css";document.getElementsByTagName("head")[0].appendChild(l)</script><script onload="hljs.initHighlighting()" src="highlight.js"></script>',
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('CODE', 'default', [
						'scriptUrl'     => 'highlight.js',
						'stylesheetUrl' => 'highlight.css'
					]);
				}
			],
			[
				'x [COLOR=red]is red[/COLOR] y',
				'x <span style="color:red">is red</span> y'
			],
			[
				'x [COLOR=red]is [COLOR=green]green[/COLOR] and red[/COLOR] y',
				'x <span style="color:red">is <span style="color:green">green</span> and red</span> y'
			],
			[
				'our [del]great [/del]leader',
				'our <del>great </del>leader'
			],
			[
				'[dl]
					[dt]Hacker
					[dd]a clever programmer
					[dt]Nerd
					[dd]technically bright but socially [s]inept[/s] awesome person
				[/dl]',
				'<dl>
					<dt>Hacker</dt>
					<dd>a clever programmer</dd>
					<dt>Nerd</dt>
					<dd>technically bright but socially <s>inept</s> awesome person</dd>
				</dl>'
			],
			[
				'Putting the EM in [em]em[/em]phasis',
				'Putting the EM in <em>em</em>phasis'
			],
			[
				'x [EMAIL]test@example.org[/EMAIL] y',
				'x <a href="mailto:test@example.org">test@example.org</a> y'
			],
			[
				'x [EMAIL=test@example.org]email[/EMAIL] y',
				'x <a href="mailto:test@example.org">email</a> y'
			],
			[
				'x [FLASH=600,400]http://example.org/foo.swf[/FLASH] y',
				'x <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://fpdownload.macromedia.com/get/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="600" height="400"><param name="movie" value="http://example.org/foo.swf"><param name="quality" value="high"><param name="wmode" value="opaque"><param name="play" value="false"><param name="loop" value="false"><param name="allowScriptAccess" value="never"><param name="allowNetworking" value="internal"><embed src="http://example.org/foo.swf" quality="high" width="600" height="400" wmode="opaque" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer" play="false" loop="false" allowscriptaccess="never" allownetworking="internal"></object> y',
			],
			[
				'x [FLASH]http://example.org/foo.swf[/FLASH] y',
				'x <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://fpdownload.macromedia.com/get/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="80" height="60"><param name="movie" value="http://example.org/foo.swf"><param name="quality" value="high"><param name="wmode" value="opaque"><param name="play" value="false"><param name="loop" value="false"><param name="allowScriptAccess" value="never"><param name="allowNetworking" value="internal"><embed src="http://example.org/foo.swf" quality="high" width="80" height="60" wmode="opaque" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer" play="false" loop="false" allowscriptaccess="never" allownetworking="internal"></object> y',
			],
			[
				'x [FLASH=10000,10000]http://example.org/foo.swf[/FLASH] y',
				'x <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://fpdownload.macromedia.com/get/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="1920" height="1080"><param name="movie" value="http://example.org/foo.swf"><param name="quality" value="high"><param name="wmode" value="opaque"><param name="play" value="false"><param name="loop" value="false"><param name="allowScriptAccess" value="never"><param name="allowNetworking" value="internal"><embed src="http://example.org/foo.swf" quality="high" width="1920" height="1080" wmode="opaque" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer" play="false" loop="false" allowscriptaccess="never" allownetworking="internal"></object> y'
			],
			[
				'x [FLASH=10000,10000]http://example.org/foo.swf[/FLASH] y',
				'x <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://fpdownload.macromedia.com/get/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="10000" height="10000"><param name="movie" value="http://example.org/foo.swf"><param name="quality" value="high"><param name="wmode" value="opaque"><param name="play" value="false"><param name="loop" value="false"><param name="allowScriptAccess" value="never"><param name="allowNetworking" value="internal"><embed src="http://example.org/foo.swf" quality="high" width="10000" height="10000" wmode="opaque" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer" play="false" loop="false" allowscriptaccess="never" allownetworking="internal"></object> y',
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('FLASH', 'default', [
						'maxHeight' => 10000,
						'maxWidth'  => 10000
					]);
				}
			],
			[
				'x [FLASH=0,0]http://example.org/foo.swf[/FLASH] y',
				'x <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://fpdownload.macromedia.com/get/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="0" height="0"><param name="movie" value="http://example.org/foo.swf"><param name="quality" value="high"><param name="wmode" value="opaque"><param name="play" value="false"><param name="loop" value="false"><param name="allowScriptAccess" value="never"><param name="allowNetworking" value="internal"><embed src="http://example.org/foo.swf" quality="high" width="0" height="0" wmode="opaque" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer" play="false" loop="false" allowscriptaccess="never" allownetworking="internal"></object> y'
			],
			[
				'x [FLASH=0,0]http://example.org/foo.swf[/FLASH] y',
				'x <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://fpdownload.macromedia.com/get/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="80" height="60"><param name="movie" value="http://example.org/foo.swf"><param name="quality" value="high"><param name="wmode" value="opaque"><param name="play" value="false"><param name="loop" value="false"><param name="allowScriptAccess" value="never"><param name="allowNetworking" value="internal"><embed src="http://example.org/foo.swf" quality="high" width="80" height="60" wmode="opaque" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer" play="false" loop="false" allowscriptaccess="never" allownetworking="internal"></object> y',
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('FLASH', 'default', [
						'minHeight' => 60,
						'minWidth'  => 80
					]);
				}
			],
			[
				'x [float=left]left[/float] y',
				'x <div style="float:left">left</div> y'
			],
			[
				'x [float=right]right[/float] y',
				'x <div style="float:right">right</div> y'
			],
			[
				'x [float=none]none[/float] y',
				'x <div style="float:none">none</div> y'
			],
			[
				'x [float=none;color:red]none[/float] y',
				'x [float=none;color:red]none[/float] y'
			],
			[
				'x [font=Arial]Arial[/font] y',
				'x <span style="font-family:Arial">Arial</span> y'
			],
			[
				'x [i]italic[/I] y',
				'x <i>italic</i> y'
			],
			[
				"[h1]h1[/h1]\n[h2]h2[/h2]\n[h3]h3[/h3]\n[h4]h4[/h4]\n[h5]h5[/h5]\n[h6]h6[/h6]\n",
				"<h1>h1</h1>\n<h2>h2</h2>\n<h3>h3</h3>\n<h4>h4</h4>\n<h5>h5</h5>\n<h6>h6</h6>\n",
			],
			[
				'[h1]h1 [b]b[/b][/h1]',
				'<h1>h1 <b>b</b></h1>'
			],
			[
				'[h1]h1 [quote]...[/quote][/h1]',
				'<h1>h1 [quote]...[/quote]</h1>'
			],
			[
				"xxxx\n[hr]\nyyyyy",
				"xxxx\n<hr>\nyyyyy"
			],
			[
				'x [B]bold [i]italic[/b][/I] y',
				'x <b>bold <i>italic</i></b> y'
			],
			[
				'x [i][b][u]...[/b][/i][/u] y',
				'x <i><b><u>...</u></b></i> y'
			],
			[
				'x [img]http://example.org/foo.png[/img] y',
				'x <img src="http://example.org/foo.png" title="" alt=""> y'
			],
			[
				'x [img=http://example.org/foo.png] y',
				'x <img src="http://example.org/foo.png" title="" alt=""> y'
			],
			[
				'x [img=http://example.org/foo.png /] y',
				'x <img src="http://example.org/foo.png" title="" alt=""> y'
			],
			[
				'our [ins]great [/ins]leader',
				'our <ins>great </ins>leader'
			],
			[
				'[justify]...[/justify]',
				'<div style="text-align:justify">...</div>'
			],
			[
				'[left]...[/left]',
				'<div style="text-align:left">...</div>'
			],
			[
				'[LIST][*]one[*]two[/LIST]',
				'<ul><li>one</li><li>two</li></ul>'
			],
			[
				'[LIST]
					[*]one
					[*]two
				[/LIST]',
				'<ul>
					<li>one</li>
					<li>two</li>
				</ul>'
			],
			[
				'[LIST]
					[*][LIST]
						[*]one.one
						[*]one.two
					[/LIST]

					[*]two
				[/LIST]',
				'<ul>
					<li><ul>
						<li>one.one</li>
						<li>one.two</li>
					</ul></li>

					<li>two</li>
				</ul>'
			],
			[
				'[LIST=1][*]one[*]two[/LIST]',
				'<ol style="list-style-type:decimal"><li>one</li><li>two</li></ol>'
			],
			[
				'[LIST=a][*]one[*]two[/LIST]',
				'<ol style="list-style-type:lower-alpha"><li>one</li><li>two</li></ol>'
			],
			[
				'[LIST=A][*]one[*]two[/LIST]',
				'<ol style="list-style-type:upper-alpha"><li>one</li><li>two</li></ol>'
			],
			[
				'[LIST=i][*]one[*]two[/LIST]',
				'<ol style="list-style-type:lower-roman"><li>one</li><li>two</li></ol>'
			],
			[
				'[LIST=I][*]one[*]two[/LIST]',
				'<ol style="list-style-type:upper-roman"><li>one</li><li>two</li></ol>'
			],
			[
				'[LIST=square][*]one[*]two[/LIST]',
				'<ul style="list-style-type:square"><li>one</li><li>two</li></ul>'
			],
			[
				'[LIST=";zoom:100"][*]one[*]two[/LIST]',
				'<ul><li>one</li><li>two</li></ul>'
			],
			[
				'[*]no <li> element without a parent',
				'[*]no &lt;li&gt; element without a parent'
			],
			[
				'[b][*]no <li> element without the right parent[/b]',
				'<b>[*]no &lt;li&gt; element without the right parent</b>'
			],
			[
				'[magnet]magnet:?xt=urn:sha1:YNCKHTQCWBTRNJIV4WNAE52SJUQCZO5C[/magnet]',
				'<a href="magnet:?xt=urn:sha1:YNCKHTQCWBTRNJIV4WNAE52SJUQCZO5C"><img alt="" src="data:image/gif;base64,R0lGODlhDAAMALMPAOXl5ewvErW1tebm5oocDkVFRePj47a2ts0WAOTk5MwVAIkcDesuEs0VAEZGRv///yH5BAEAAA8ALAAAAAAMAAwAAARB8MnnqpuzroZYzQvSNMroUeFIjornbK1mVkRzUgQSyPfbFi/dBRdzCAyJoTFhcBQOiYHyAABUDsiCxAFNWj6UbwQAOw==" style="vertical-align:middle;border:0;margin:0 5px 0 0">magnet:?xt=urn:sha1:YNCKHTQCWBTRNJIV4WNAE52SJUQCZO5C</a>'
			],
			[
				'[magnet=magnet:?xt=urn:sha1:YNCKHTQCWBTRNJIV4WNAE52SJUQCZO5C]Download me[/magnet]',
				'<a href="magnet:?xt=urn:sha1:YNCKHTQCWBTRNJIV4WNAE52SJUQCZO5C"><img alt="" src="data:image/gif;base64,R0lGODlhDAAMALMPAOXl5ewvErW1tebm5oocDkVFRePj47a2ts0WAOTk5MwVAIkcDesuEs0VAEZGRv///yH5BAEAAA8ALAAAAAAMAAwAAARB8MnnqpuzroZYzQvSNMroUeFIjornbK1mVkRzUgQSyPfbFi/dBRdzCAyJoTFhcBQOiYHyAABUDsiCxAFNWj6UbwQAOw==" style="vertical-align:middle;border:0;margin:0 5px 0 0">Download me</a>'
			],
			[
				'[NOPARSE][b]no bold[/b][/NOPARSE] [b]bold[/b]',
				'[b]no bold[/b] <b>bold</b>'
			],
			[
				"[NOPARSE]still converts new\nlines[/NOPARSE]",
				"still converts new<br>\nlines"
			],
			[
				'[QUOTE]...text...[/QUOTE]',
				'<blockquote class="uncited"><div>...text...</div></blockquote>'
			],
			[
				'[QUOTE=namehere]...text...[/QUOTE]',
				'<blockquote><div><cite>namehere wrote:</cite>...text...</div></blockquote>'
			],
			[
				'[QUOTE=namehere]...text...[/QUOTE]',
				'<blockquote><div><cite>namehere ha escrit:</cite>...text...</div></blockquote>',
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('QUOTE', 'default', [
						'authorStr' => '<xsl:value-of select="@author"/> ha escrit:'
					]);
				}
			],
			[
				'my quote:

					[QUOTE]...text...[/QUOTE]

				follow-up',
				'my quote:

					<blockquote class="uncited"><div>...text...</div></blockquote>

				follow-up'
			],
			[
				'my quote:


					[QUOTE]...text...[/QUOTE]


				follow-up',
				'my quote:<br>


					<blockquote class="uncited"><div>...text...</div></blockquote>

<br>
				follow-up'
			],
			[
				'[right]...[/right]',
				'<div style="text-align:right">...</div>'
			],
			[
				'x [s]strikethrough[/s] y',
				'x <s>strikethrough</s> y'
			],
			[
				'x [size=16]bigger[/size] y',
				'x <span style="font-size:16px">bigger</span> y'
			],
			[
				'x [size=1]smaller[/size] y',
				'x <span style="font-size:8px">smaller</span> y'
			],
			[
				'x [size=160]biggest[/size] y',
				'x <span style="font-size:36px">biggest</span> y'
			],
			[
				'x [size=160]biggest[/size] y',
				'x <span style="font-size:160px">biggest</span> y',
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('SIZE', 'default', ['max' => 300]);
				}
			],
			[
				'x [size=1]smallest[/size] y',
				'x <span style="font-size:8px">smallest</span> y'
			],
			[
				'x [size=1]smallest[/size] y',
				'x <span style="font-size:1px">smallest</span> y',
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('SIZE', 'default', ['min' => 1]);
				}
			],
			[
				"Spoiler ahead!\n" .
				"[spoiler]Now you're spoiled[/spoiler]",
				"Spoiler ahead!\n" .
				'<div class="spoiler"><div class="spoiler-header"><button onclick="var c=this.parentNode.nextSibling.style,s=this.firstChild.style,h=this.lastChild.style;\'\'!=c.display?(c.display=h.display=\'\',s.display=\'none\'):(c.display=h.display=\'none\',s.display=\'\')"><span>Show</span><span style="display:none">Hide</span></button><span class="spoiler-title">Spoiler: </span></div><div class="spoiler-content" style="display:none">Now you\'re spoiled</div></div>',
			],
			[
				"Spoiler ahead!\n" .
				'[spoiler="your spoilage status"]Now you\'re spoiled[/spoiler]',
				"Spoiler ahead!\n" . 
				'<div class="spoiler"><div class="spoiler-header"><button onclick="var c=this.parentNode.nextSibling.style,s=this.firstChild.style,h=this.lastChild.style;\'\'!=c.display?(c.display=h.display=\'\',s.display=\'none\'):(c.display=h.display=\'none\',s.display=\'\')"><span>Show</span><span style="display:none">Hide</span></button><span class="spoiler-title">Spoiler: your spoilage status</span></div><div class="spoiler-content" style="display:none">Now you\'re spoiled</div></div>'
			],
			[
				"Spoiler ahead!\n" .
				"[spoiler][spoiler='Last chance']Now you're spoiled[/spoiler][/spoiler]",
				"Spoiler ahead!\n" .
				'<div class="spoiler"><div class="spoiler-header"><button onclick="var c=this.parentNode.nextSibling.style,s=this.firstChild.style,h=this.lastChild.style;\'\'!=c.display?(c.display=h.display=\'\',s.display=\'none\'):(c.display=h.display=\'none\',s.display=\'\')"><span>Show</span><span style="display:none">Hide</span></button><span class="spoiler-title">Spoiler: </span></div><div class="spoiler-content" style="display:none"><div class="spoiler"><div class="spoiler-header"><button onclick="var c=this.parentNode.nextSibling.style,s=this.firstChild.style,h=this.lastChild.style;\'\'!=c.display?(c.display=h.display=\'\',s.display=\'none\'):(c.display=h.display=\'none\',s.display=\'\')"><span>Show</span><span style="display:none">Hide</span></button><span class="spoiler-title">Spoiler: Last chance</span></div><div class="spoiler-content" style="display:none">Now you\'re spoiled</div></div></div></div>'
			],
			[
				"Spoiler ahead!\n" .
				"[spoiler]Now you're spoiled[/spoiler]",
				"Spoiler ahead!\n" . 
				'<div class="spoiler"><div class="spoiler-header"><button onclick="var c=this.parentNode.nextSibling.style,s=this.firstChild.style,h=this.lastChild.style;\'\'!=c.display?(c.display=h.display=\'\',s.display=\'none\'):(c.display=h.display=\'none\',s.display=\'\')"><span>Montrer</span><span style="display:none">Cacher</span></button><span class="spoiler-title">Spoiler : </span></div><div class="spoiler-content" style="display:none">Now you\'re spoiled</div></div>',
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('SPOILER', 'default', [
						'showStr'    => 'Montrer',
						'hideStr'    => 'Cacher',
						'spoilerStr' => 'Spoiler :'
					]);
				}
			],
			[
				'Some [strong]strong[/strong] words',
				'Some <strong>strong</strong> words'
			],
			[
				'x [sub]sub[/sub] y',
				'x <sub>sub</sub> y'
			],
			[
				'x [sup]sup[/sup] y',
				'x <sup>sup</sup> y'
			],
			[
				'x [u]underline[/u] y',
				'x <u>underline</u> y'
			],
			[
				'x [url]http://example.org[/url] y',
				'x <a href="http://example.org">http://example.org</a> y'
			],
			[
				'x [url]https://example.org[/url] y',
				'x <a href="https://example.org">https://example.org</a> y'
			],
			[
				'x [url=http://example.org]text[/url] y',
				'x <a href="http://example.org">text</a> y'
			],
			[
				'x [url=http://example.org title="my title"]text[/url] y',
				'x <a href="http://example.org" title="my title">text</a> y'
			],
			[
				'x [url=http://example.org][url=http://example.org]text[/url][/url] y',
				'x <a href="http://example.org">[url=http://example.org]text</a>[/url] y'
			],
			[
				'x [url:123=http://example.org][url=http://example.org]text[/url][/url:123] y',
				'x <a href="http://example.org">[url=http://example.org]text[/url]</a> y'
			],
			[
				'x [url=http://example.org][EMAIL]test@example.org[/EMAIL][/url] y',
				'x <a href="http://example.org">[EMAIL]test@example.org[/EMAIL]</a> y'
			],
			[
				'x [url=javascript:foo]text[/url] y',
				'x [url=javascript:foo]text[/url] y'
			],
			[
				'x [url=http://example.org]text[/url] [url=http://evil.example.org]evil[/url] y',
				'x <a href="http://example.org">text</a> <a href="http://evil.example.org">evil</a> y'
			],
			[
				'x [url=http://example.org]text[/url] [url=http://evil.example.org]evil[/url] y',
				'x <a href="http://example.org">text</a> [url=http://evil.example.org]evil[/url] y',
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('evil.example.org');
				}
			],
			[
				'x [url=//example.org]text[/url] y',
				'x <a href="//example.org">text</a> y'
			],
			[
				'x [url=//example.org]text[/url] y',
				'x [url=//example.org]text[/url] y',
				function ($configurator)
				{
					$configurator->urlConfig->requireScheme();
				}
			],
			[
				'x [url=foo://example.org]text[/url] y',
				'x [url=foo://example.org]text[/url] y'
			],
			[
				'x [url=foo://example.org]text[/url] y',
				'x <a href="foo://example.org">text</a> y',
				function ($configurator)
				{
					$configurator->urlConfig->allowScheme('foo');
				}
			],
			[
				'x [url=http://example.org][flash=10,20]http://example.org/foo.swf[/flash][/url] y',
				'x <a href="http://example.org">[flash=10,20]http://example.org/foo.swf[/flash]</a> y'
			],
			[
				'x [url=http://example.org][img]http://example.org/foo.png[/img][/url] y',
				'x <a href="http://example.org"><img src="http://example.org/foo.png" title="" alt=""></a> y'
			],
			[
				'x [url=http://example.org][img]http://example.org/foo.png[/img][/url] y',
				'x <a href="http://example.org">[img]http://example.org/foo.png[/img]</a> y',
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('url');
					$configurator->BBCodes->addFromRepository('img');
					$configurator->tags['url']->rules->denyDescendant('img');
				}
			],
			[
				'x [var]var[/var] y',
				'x <var>var</var> y'
			],
			[
				'[var]x[sub][var]i[/var][/sub][/var]',
				'<var>x<sub><var>i</var></sub></var>'
			],
		];
	}
}