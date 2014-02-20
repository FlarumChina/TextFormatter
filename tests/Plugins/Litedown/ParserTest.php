<?php

namespace s9e\TextFormatter\Tests\Plugins\Litedown;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Litedown\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Litedown\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavaScriptRunner;
	use RenderingTestsRunner;

	public function getParsingTests()
	{
		return self::fixTests([
			// Paragraphs and quotes
			[
				'foo',
				'<t><p>foo</p></t>'
			],
			[
				"foo\n\nbar",
				"<t><p>foo</p>\n\n<p>bar</p></t>"
			],
			[
				'> foo',
				'<r><QUOTE><i>&gt; </i><p>foo</p></QUOTE></r>'
			],
			[
				[
					'> > foo',
					'> ',
					'> bar',
					'',
					'baz'
				],
				[
					'<r><QUOTE><QUOTE><i>&gt; &gt; </i><p>foo</p></QUOTE>',
					'<i>&gt; </i>',
					'<i>&gt; </i><p>bar</p></QUOTE>',
					'',
					'<p>baz</p></r>'
				]
			],
			[
				[
					'> > foo',
					'> ',
					'> > bar',
					'',
					'baz'
				],
				[
					'<r><QUOTE><QUOTE><i>&gt; &gt; </i><p>foo</p>',
					'<i>&gt; </i>',
					'<i>&gt; &gt; </i><p>bar</p></QUOTE></QUOTE>',
					'',
					'<p>baz</p></r>'
				]
			],
			[
				[
					'> > foo',
					'',
					'> > bar',
					'',
					'baz'
				],
				[
					'<r><QUOTE><QUOTE><i>&gt; &gt; </i><p>foo</p>',
					'',
					'<i>&gt; &gt; </i><p>bar</p></QUOTE></QUOTE>',
					'',
					'<p>baz</p></r>'
				]
			],
			[
				[
					'> foo',
					'bar',
					'baz',
					'',
					'quux'
				],
				[
					'<r><QUOTE><i>&gt; </i><p>foo',
					'bar',
					'baz</p></QUOTE>',
					'',
					'<p>quux</p></r>'
				]
			],
			[
				[
					'foo',
					'> bar',
					'baz',
					'',
					'quux'
				],
				[
					'<r><p>foo</p>',
					'<QUOTE><i>&gt; </i><p>bar',
					'baz</p></QUOTE>',
					'',
					'<p>quux</p></r>'
				]
			],
			// Indented code blocks
			[
				[
					'    code',
					'    more code',
					'',
					'foo'
				],
				[
					'<r><i>    </i><CODE>code',
					'<i>    </i>more code</CODE>',
					'',
					'<p>foo</p></r>'
				]
			],
			[
				[
					'    code',
					"\tmore code",
					'',
					'foo'
				],
				[
					'<r><i>    </i><CODE>code',
					"<i>\t</i>more code</CODE>",
					'',
					'<p>foo</p></r>'
				]
			],
			[
				[
					'    code',
					"   \tmore code",
					'',
					'foo'
				],
				[
					'<r><i>    </i><CODE>code',
					"<i>   \t</i>more code</CODE>",
					'',
					'<p>foo</p></r>'
				]
			],
			[
				[
					'    code',
					"    \tmore code",
					'',
					'foo'
				],
				[
					'<r><i>    </i><CODE>code',
					"<i>    </i>\tmore code</CODE>",
					'',
					'<p>foo</p></r>'
				]
			],
			[
				[
					'bar',
					'',
					'    code',
					'    more code',
					'',
					'foo'
				],
				[
					'<r><p>bar</p>',
					'',
					'<i>    </i><CODE>code',
					'<i>    </i>more code</CODE>',
					'',
					'<p>foo</p></r>'
				]
			],
			[
				[
					'bar',
					'',
					'    code',
					'',
					'    more code',
					'',
					'foo'
				],
				[
					'<r><p>bar</p>',
					'',
					'<i>    </i><CODE>code',
					'',
					'<i>    </i>more code</CODE>',
					'',
					'<p>foo</p></r>'
				]
			],
			[
				[
					'foo',
					'    bar',
					'',
					'foo'
				],
				[
					'<t><p>foo',
					'    bar</p>',
					'',
					'<p>foo</p></t>'
				]
			],
			[
				[
					'>     code',
					'>     more code',
					'> ',
					'> foo',
					'',
					'bar'
				],
				[
					'<r><QUOTE><i>&gt;     </i><CODE>code',
					'<i>&gt;     </i>more code</CODE>',
					'<i>&gt; </i>',
					'<i>&gt; </i><p>foo</p></QUOTE>',
					'',
					'<p>bar</p></r>'
				]
			],
			[
				[
					'>     code',
					'>     more code',
					'> ',
					'> >     another block',
					'',
					'bar'
				],
				[
					'<r><QUOTE><i>&gt;     </i><CODE>code',
					'<i>&gt;     </i>more code</CODE>',
					'<i>&gt; </i>',
					'<QUOTE><i>&gt; &gt;     </i><CODE>another block</CODE></QUOTE></QUOTE>',
					'',
					'<p>bar</p></r>'
				]
			],
			[
				[
					'> >     code',
					'> >     more code',
					'> ',
					'>       another block',
					'',
					'bar'
				],
				[
					'<r><QUOTE><QUOTE><i>&gt; &gt;     </i><CODE>code',
					'<i>&gt; &gt;     </i>more code</CODE></QUOTE>',
					'<i>&gt; </i>',
					'<i>&gt;     </i><CODE>  another block</CODE></QUOTE>',
					'',
					'<p>bar</p></r>'
				]
			],
			[
				[
					'    ## foo ##',
					'',
					'bar'
				],
				[
					'<r><i>    </i><CODE>## foo ##</CODE>',
					'',
					'<p>bar</p></r>'
				]
			],
			[
				[
					'*foo',
					'',
					'    *bar',
					'baz*'
				],
				[
					'<r><p>*foo</p>',
					'',
					'<i>    </i><CODE>*bar</CODE>',
					'<p>baz*</p></r>'
				]
			],
			[
				[
					'    foo',
					'*bar*'
				],
				[
					'<r><i>    </i><CODE>foo</CODE>',
					'<p><EM><s>*</s>bar<e>*</e></EM></p></r>'
				]
			],
			// Lists
			[
				[
					'* 0',
					' * 1',
					'  * 2',
					'   * 3',
					'    * 4',
					'     * 5',
					'      * 6',
					'       * 7',
					'        * 8',
					'         * 9'
				],
				[
					'<r><LIST><LI><s>* </s>0',
					' <LIST><LI><s>* </s>1</LI>',
					'  <LI><s>* </s>2</LI>',
					'   <LI><s>* </s>3</LI>',
					'    <LI><s>* </s>4',
					'     <LIST><LI><s>* </s>5</LI>',
					'      <LI><s>* </s>6</LI>',
					'       <LI><s>* </s>7</LI>',
					'        <LI><s>* </s>8',
					'         <LIST><LI><s>* </s>9</LI></LIST></LI></LIST></LI></LIST></LI></LIST></r>'
				]
			],
			[
				[
					'+ one',
					'+ two'
				],
				[
					'<r><LIST><LI><s>+ </s>one</LI>',
					'<LI><s>+ </s>two</LI></LIST></r>'
				]
			],
			[
				[
					'- one',
					'',
					'- two'
				],
				[
					'<r><LIST><LI><s>- </s><p>one</p></LI>',
					'',
					'<LI><s>- </s><p>two</p></LI></LIST></r>'
				]
			],
			[
				[
					'- one',
					'  - foo',
					'  - bar',
					'',
					'- two',
					'  - bar',
					'  - baz',
					'',
					'- three'
				],
				[
					'<r><LIST><LI><s>- </s><p>one</p>',
					'  <LIST><LI><s>- </s>foo</LI>',
					'  <LI><s>- </s>bar</LI></LIST></LI>',
					'',
					'<LI><s>- </s><p>two</p>',
					'  <LIST><LI><s>- </s>bar</LI>',
					'  <LI><s>- </s>baz</LI></LIST></LI>',
					'',
					'<LI><s>- </s><p>three</p></LI></LIST></r>',
				]
			],
			[
				[
					'- one',
					'',
					'  - foo',
					'  - bar',
					'',
					'- two',
					'',
					'- three'
				],
				[
					'<r><LIST><LI><s>- </s><p>one</p>',
					'',
					'  <LIST><LI><s>- </s>foo</LI>',
					'  <LI><s>- </s>bar</LI></LIST></LI>',
					'',
					'<LI><s>- </s><p>two</p></LI>',
					'',
					'<LI><s>- </s><p>three</p></LI></LIST></r>',
				]
			],
			[
				[
					' * **foo**',
					' * *bar*'
				],
				[
					'<r> <LIST><LI><s>* </s><STRONG><s>**</s>foo<e>**</e></STRONG></LI>',
					' <LI><s>* </s><EM><s>*</s>bar<e>*</e></EM></LI></LIST></r>'
				]
			],
			[
				[
					' - *foo',
					'   bar*'
				],
				[
					'<r> <LIST><LI><s>- </s><EM><s>*</s>foo',
					'   bar<e>*</e></EM></LI></LIST></r>'
				]
			],
			[
				[
					' - *foo',
					' - bar*'
				],
				[
					'<r> <LIST><LI><s>- </s>*foo</LI>',
					' <LI><s>- </s>bar*</LI></LIST></r>'
				]
			],
			[
				[
					' * foo',
					'',
					'',
					'   bar',
					'',
					' * baz'
				],
				[
					'<r> <LIST><LI><s>* </s><p>foo</p>',
					'',
					'',
					'   <p>bar</p></LI>',
					'',
					' <LI><s>* </s><p>baz</p></LI></LIST></r>'
				]
			],
			[
				[
					'1. one',
					'2. two'
				],
				[
					'<r><LIST type="decimal"><LI><s>1. </s>one</LI>',
					'<LI><s>2. </s>two</LI></LIST></r>'
				]
			],
			[
				[
					'* foo',
					'',
					'> bar'
				],
				[
					'<r><LIST><LI><s>* </s>foo</LI></LIST>',
					'',
					'<QUOTE><i>&gt; </i><p>bar</p></QUOTE></r>'
				]
			],
			// atx-style headers
			[
				'# H1',
				'<r><H1><s># </s>H1</H1></r>'
			],
			[
				'###### H6',
				'<r><H6><s>###### </s>H6</H6></r>'
			],
			[
				'####### H7',
				'<r><H6><s>####### </s>H7</H6></r>'
			],
			[
				'# H1 #',
				'<r><H1><s># </s>H1<e> #</e></H1></r>'
			],
			[
				'### H3 # H3 ####',
				'<r><H3><s>### </s>H3 # H3<e> ####</e></H3></r>'
			],
			[
				'### foo *bar*',
				'<r><H3><s>### </s>foo <EM><s>*</s>bar<e>*</e></EM></H3></r>'
			],
			[
				"*foo\n### bar*",
				"<r><p>*foo</p>\n<H3><s>### </s>bar*</H3></r>"
			],
			[
				"*foo\n### bar*\nbaz*",
				"<r><p>*foo</p>\n<H3><s>### </s>bar*</H3>\n<p>baz*</p></r>"
			],
			[
				"foo\n\n### bar\n\nbaz",
				"<r><p>foo</p>\n\n<H3><s>### </s>bar</H3>\n\n<p>baz</p></r>"
			],
			[
				"foo\n\n### bar\n\nbaz",
				"<r><p>foo</p>\n\n<H3><s>### </s>bar</H3>\n\n<p>baz</p></r>"
			],
			[
				[
					'> > foo',
					'> ',
					'> # BAR',
					'> ',
					'> baz',
					'',
					'text'
				],
				[
					'<r><QUOTE><QUOTE><i>&gt; &gt; </i><p>foo</p></QUOTE>',
					'<i>&gt; </i>',
					'<i>&gt; </i><H1><s># </s>BAR</H1>',
					'<i>&gt; </i>',
					'<i>&gt; </i><p>baz</p></QUOTE>',
					'',
					'<p>text</p></r>'
				]
			],
			// Setext-style headers
			[
				[
					'foo',
					'===',
					'bar'
				],
				[
					'<r><H1>foo<e>',
					'===</e></H1>',
					'<p>bar</p></r>'
				]
			],
			[
				[
					'foo',
					'---',
					'bar'
				],
				[
					'<r><H2>foo<e>',
					'---</e></H2>',
					'<p>bar</p></r>'
				]
			],
			[
				[
					'foo',
					'=-=',
					'bar'
				],
				[
					'<t><p>foo',
					'=-=',
					'bar</p></t>'
				]
			],
			[
				[
					'foo',
					'= = =',
					'bar'
				],
				[
					'<t><p>foo',
					'= = =',
					'bar</p></t>'
				]
			],
			[
				[
					'> foo',
					'> -'
				],
				[
					'<r><QUOTE><i>&gt; </i><H2>foo<e>',
					'&gt; -</e></H2></QUOTE></r>'
				]
			],
			[
				[
					'> foo',
					'> > -'
				],
				[
					'<r><QUOTE><i>&gt; </i><p>foo</p>',
					'<QUOTE><i>&gt; &gt; </i><p>-</p></QUOTE></QUOTE></r>'
				]
			],
			[
				// NOTE: implementations vary wildly on that one. The old Markdown and PHP Markdown
				//       both interpret it as an header whose text content is "> foo" but most other
				//       implementations interpret it as an header inside of a blockquote. Here we
				//       choose a different path: ignore the header altogether to prevent an
				//       accidental dash to turn the last line of a blockquote into a header
				[
					'> foo',
					'-'
				],
				[
					'<r><QUOTE><i>&gt; </i><p>foo',
					'-</p></QUOTE></r>'
				]
			],
			[
				// NOTE: implementations vary wildly. Same as for blockquotes, a loose dash should
				//       not create headers
				[
					'- foo',
					'-'
				],
				[
					'<r><LIST><LI><s>- </s>foo',
					'-</LI></LIST></r>'
				]
			],
			[
				[
					'    code',
					'-'
				],
				[
					'<r><i>    </i><CODE>code</CODE>',
					'<p>-</p></r>'
				]
			],
			[
				'-',
				'<t><p>-</p></t>'
			],
			[
				" \n-",
				"<t> \n<p>-</p></t>"
			],
			[
				[
					'## foo',
					'======'
				],
				[
					'<r><H2><s>## </s>foo</H2>',
					'<p>======</p></r>'
				]
			],
			[
				[
					'foo ',
					'===='
				],
				[
					'<r><H1>foo<e> ',
					'====</e></H1></r>'
				]
			],
			[
				[
					'foo',
					'===',
					'==='
				],
				[
					'<r><H1>foo<e>',
					'===</e></H1>',
					'<p>===</p></r>'
				]
			],
			// Horizontal rules
			[
				[
					'foo',
					'',
					'---',
					'',
					'bar'
				],
				[
					'<r><p>foo</p>',
					'',
					'<HR>---</HR>',
					'',
					'<p>bar</p></r>'
				]
			],
			[
				[
					'foo',
					' _ _ _ ',
					'bar'
				],
				[
					'<r><p>foo</p>',
					'<HR> _ _ _ </HR>',
					'<p>bar</p></r>'
				]
			],
			[
				[
					'foo',
					'___',
					'bar'
				],
				[
					'<r><p>foo</p>',
					'<HR>___</HR>',
					'<p>bar</p></r>'
				]
			],
			[
				[
					'foo',
					'***',
					'bar'
				],
				[
					'<r><p>foo</p>',
					'<HR>***</HR>',
					'<p>bar</p></r>'
				]
			],
			[
				[
					'foo',
					'* * *',
					'bar'
				],
				[
					'<r><p>foo</p>',
					'<HR>* * *</HR>',
					'<p>bar</p></r>'
				]
			],
			[
				[
					'foo',
					'   * * * * *   ',
					'bar'
				],
				[
					'<r><p>foo</p>',
					'<HR>   * * * * *   </HR>',
					'<p>bar</p></r>'
				]
			],
			[
				[
					' - foo',
					'   ***',
					'   bar'
				],
				[
					'<r> <LIST><LI><s>- </s>foo',
					'   ***',
					'   bar</LI></LIST></r>'
				]
			],
			[
				'>  *** ',
				'<r><QUOTE><i>&gt; </i><HR> *** </HR></QUOTE></r>'
			],
			// Links
			[
				'Go to [that site](http://example.org) now!',
				'<r><p>Go to <URL url="http://example.org"><s>[</s>that site<e>](http://example.org)</e></URL> now!</p></r>'
			],
			[
				'Go to [that site] (http://example.org) now!',
				'<r><p>Go to <URL url="http://example.org"><s>[</s>that site<e>] (http://example.org)</e></URL> now!</p></r>'
			],
			[
				'En route to [Mars](http://en.wikipedia.org/wiki/Mars_(disambiguation\))!',
				'<r><p>En route to <URL url="http://en.wikipedia.org/wiki/Mars_%28disambiguation%29"><s>[</s>Mars<e>](http://en.wikipedia.org/wiki/Mars_(disambiguation\))</e></URL>!</p></r>'
			],
			[
				'Go to [\\[x\\[x\\]x\\]](http://example.org/?foo[]=1&bar\\[\\]=1) now!',
				'<r><p>Go to <URL url="http://example.org/?foo%5B%5D=1&amp;bar%5B%5D=1"><s>[</s>\\[x\\[x\\]x\\]<e>](http://example.org/?foo[]=1&amp;bar\\[\\]=1)</e></URL> now!</p></r>'
			],
			[
				'Check out my [~~lame~~ cool site](http://example.org) now!',
				'<r><p>Check out my <URL url="http://example.org"><s>[</s><DEL><s>~~</s>lame<e>~~</e></DEL> cool site<e>](http://example.org)</e></URL> now!</p></r>'
			],
			[
				'This is [an example](http://example.com/ "Link title") inline link.',
				'<r><p>This is <URL title="Link title" url="http://example.com/"><s>[</s>an example<e>](http://example.com/ "Link title")</e></URL> inline link.</p></r>'
			],
			[
				'This is [an example](http://example.com/ ""Link title"") inline link.',
				'<r><p>This is <URL title="&quot;Link title&quot;" url="http://example.com/"><s>[</s>an example<e>](http://example.com/ ""Link title"")</e></URL> inline link.</p></r>'
			],
			[
				'[not a link]',
				'<t><p>[not a link]</p></t>'
			],
			// Images
			[
				'.. ![Alt text](http://example.org/img.png) ..',
				'<r><p>.. <IMG alt="Alt text" src="http://example.org/img.png"><s>![</s>Alt text<e>](http://example.org/img.png)</e></IMG> ..</p></r>'
			],
			[
				'.. ![Alt text](http://example.org/img.png "Image title") ..',
				'<r><p>.. <IMG alt="Alt text" src="http://example.org/img.png" title="Image title"><s>![</s>Alt text<e>](http://example.org/img.png "Image title")</e></IMG> ..</p></r>'
			],
			[
				'.. ![Alt \\[text\\]](http://example.org/img.png "\\"Image title\\"") ..',
				'<r><p>.. <IMG alt="Alt [text]" src="http://example.org/img.png" title="&quot;Image title&quot;"><s>![</s>Alt \\[text\\]<e>](http://example.org/img.png "\\"Image title\\"")</e></IMG> ..</p></r>'
			],
			[
				'.. ![Alt text](http://example.org/img.png "Image (title)") ..',
				'<r><p>.. <IMG alt="Alt text" src="http://example.org/img.png" title="Image (title)"><s>![</s>Alt text<e>](http://example.org/img.png "Image (title)")</e></IMG> ..</p></r>'
			],
			// Images in links
			[
				'.. [![Alt text](http://example.org/img.png)](http://example.org/) ..',
				'<r><p>.. <URL url="http://example.org/"><s>[</s><IMG alt="Alt text" src="http://example.org/img.png"><s>![</s>Alt text<e>](http://example.org/img.png)</e></IMG><e>](http://example.org/)</e></URL> ..</p></r>'
			],
			// Inline code
			[
				'.. `foo` `bar` ..',
				'<r><p>.. <C><s>`</s>foo<e>`</e></C> <C><s>`</s>bar<e>`</e></C> ..</p></r>'
			],
			[
				'.. `foo `` bar` ..',
				'<r><p>.. <C><s>`</s>foo `` bar<e>`</e></C> ..</p></r>'
			],
			[
				'.. `foo ``` bar` ..',
				'<r><p>.. <C><s>`</s>foo ``` bar<e>`</e></C> ..</p></r>'
			],
			[
				'.. ``foo`` ``bar`` ..',
				'<r><p>.. <C><s>``</s>foo<e>``</e></C> <C><s>``</s>bar<e>``</e></C> ..</p></r>'
			],
			[
				'.. ``foo `bar` baz`` ..',
				'<r><p>.. <C><s>``</s>foo `bar` baz<e>``</e></C> ..</p></r>'
			],
			[
				'.. `foo\\` \\`b\\\\ar` ..',
				'<r><p>.. <C><s>`</s>foo\\` \\`b\\\\ar<e>`</e></C> ..</p></r>'
			],
			[
				'.. `[foo](http://example.org)` ..',
				'<r><p>.. <C><s>`</s>[foo](http://example.org)<e>`</e></C> ..</p></r>'
			],
			[
				'.. `![foo](http://example.org)` ..',
				'<r><p>.. <C><s>`</s>![foo](http://example.org)<e>`</e></C> ..</p></r>'
			],
			[
				'.. `x` ..',
				'<r><p>.. <C><s>`</s>x<e>`</e></C> ..</p></r>'
			],
			[
				'.. ``x`` ..',
				'<r><p>.. <C><s>``</s>x<e>``</e></C> ..</p></r>'
			],
			[
				"`foo\nbar`",
				"<r><p><C><s>`</s>foo\nbar<e>`</e></C></p></r>"
			],
			[
				"`foo\n\nbar`",
				"<t><p>`foo</p>\n\n<p>bar`</p></t>"
			],
			// Strikethrough
			[
				'.. ~~foo~~ ~~bar~~ ..',
				'<r><p>.. <DEL><s>~~</s>foo<e>~~</e></DEL> <DEL><s>~~</s>bar<e>~~</e></DEL> ..</p></r>'
			],
			[
				'.. ~~foo~bar~~ ..',
				'<r><p>.. <DEL><s>~~</s>foo~bar<e>~~</e></DEL> ..</p></r>'
			],
			[
				'.. ~~foo\\~~ ~~bar~~ ..',
				'<r><p>.. <DEL><s>~~</s>foo\\~~ <e>~~</e></DEL>bar~~ ..</p></r>'
			],
			[
				'.. ~~~~ ..',
				'<t><p>.. ~~~~ ..</p></t>'
			],
			[
				"~~foo\nbar~~",
				"<r><p><DEL><s>~~</s>foo\nbar<e>~~</e></DEL></p></r>"
			],
			[
				"~~foo\n\nbar~~",
				"<t><p>~~foo</p>\n\n<p>bar~~</p></t>"
			],
			// Superscript
			[
				'.. foo^baar^baz 1^2 ..',
				'<r><p>.. foo<SUP><s>^</s>baar<SUP><s>^</s>baz</SUP></SUP> 1<SUP><s>^</s>2</SUP> ..</p></r>'
			],
			[
				'.. \\^_^ ..',
				'<t><p>.. \^_^ ..</p></t>'
			],
			// Emphasis
			[
				'xx ***x*****x** xx',
				'<r><p>xx <STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG><STRONG><s>**</s>x<e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx ***x****x* xx',
				'<r><p>xx <STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG><EM><s>*</s>x<e>*</e></EM> xx</p></r>'
			],
			[
				'xx ***x*** xx',
				'<r><p>xx <STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx ***x**x* xx',
				'<r><p>xx <EM><s>*</s><STRONG><s>**</s>x<e>**</e></STRONG>x<e>*</e></EM> xx</p></r>'
			],
			[
				'xx ***x*x** xx',
				'<r><p>xx <STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM>x<e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx **x*****x*** xx',
				'<r><p>xx <STRONG><s>**</s>x<e>**</e></STRONG><STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx **x****x** xx',
				'<r><p>xx <STRONG><s>**</s>x<e>**</e></STRONG><STRONG><s>**</s>x<e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx **x***x* xx',
				'<r><p>xx <STRONG><s>**</s>x<e>**</e></STRONG><EM><s>*</s>x<e>*</e></EM> xx</p></r>'
			],
			[
				'xx **x** xx',
				'<r><p>xx <STRONG><s>**</s>x<e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx **x*x** xx',
				'<r><p>xx <STRONG><s>**</s>x*x<e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx *x*****x*** xx',
				'<r><p>xx <EM><s>*</s>x<e>*</e></EM>*<STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx *x****x*** xx',
				'<r><p>xx <EM><s>*</s>x<e>*</e></EM><STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx *x**x* xx',
				'<r><p>xx <EM><s>*</s>x**x<e>*</e></EM> xx</p></r>'
			],
			[
				'xx *x* xx',
				'<r><p>xx <EM><s>*</s>x<e>*</e></EM> xx</p></r>'
			],
			[
				'xx *x**x*x** xx',
				'<r><p>xx <EM><s>*</s>x<STRONG><s>**</s>x</STRONG><e>*</e></EM><STRONG>x<e>**</e></STRONG> xx</p></r>'
			],
			[
				"*foo\nbar*",
				"<r><p><EM><s>*</s>foo\nbar<e>*</e></EM></p></r>"
			],
			[
				"*foo\n\nbar*",
				"<t><p>*foo</p>\n\n<p>bar*</p></t>"
			],
			[
				"***foo*\n\nbar**",
				"<r><p>**<EM><s>*</s>foo<e>*</e></EM></p>\n\n<p>bar**</p></r>"
			],
			[
				"***foo**\n\nbar*",
				"<r><p>*<STRONG><s>**</s>foo<e>**</e></STRONG></p>\n\n<p>bar*</p></r>"
			],
			[
				'xx _x_ xx',
				'<r><p>xx <EM><s>_</s>x<e>_</e></EM> xx</p></r>'
			],
			[
				'xx __x__ xx',
				'<r><p>xx <STRONG><s>__</s>x<e>__</e></STRONG> xx</p></r>'
			],
			[
				'xx foo_bar_baz xx',
				'<t><p>xx foo_bar_baz xx</p></t>'
			],
			[
				'xx foo__bar__baz xx',
				'<r><p>xx foo<STRONG><s>__</s>bar<e>__</e></STRONG>baz xx</p></r>'
			],
			[
				'x _foo_',
				'<r><p>x <EM><s>_</s>foo<e>_</e></EM></p></r>'
			],
			[
				'_foo_ x',
				'<r><p><EM><s>_</s>foo<e>_</e></EM> x</p></r>'
			],
			[
				'_foo_',
				'<r><p><EM><s>_</s>foo<e>_</e></EM></p></r>'
			],
			[
				'xx ***x******x*** xx',
				'<r><p>xx <STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG><STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx ***x*******x*** xx',
				'<r><p>xx <STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG>*<STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx *****x***** xx',
				'<r><p>xx **<STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG>** xx</p></r>'
			],
			[
				'xx **x*x*** xx',
				'<r><p>xx <STRONG><s>**</s>x<EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx *x**x*** xx',
				'<r><p>xx <EM><s>*</s>x<STRONG><s>**</s>x<e>**</e></STRONG><e>*</e></EM> xx</p></r>'
			],
			[
				'\\\\*foo*',
				'<r><p>\\\\<EM><s>*</s>foo<e>*</e></EM></p></r>'
			],
			[
				'*\\\\*foo*',
				'<r><p><EM><s>*</s>\\\\<e>*</e></EM>foo*</p></r>'
			]
		]);
	}

	public function getRenderingTests()
	{
		return self::fixTests([
			[
				'> foo',
				'<blockquote><p>foo</p></blockquote>'
			],
			[
				[
					'> > foo',
					'> ',
					'> bar',
					'',
					'baz'
				],
				[
					'<blockquote><blockquote><p>foo</p></blockquote>',
					'',
					'<p>bar</p></blockquote>',
					'',
					'<p>baz</p>'
				]
			],
			[
				[
					'foo',
					'',
					'## bar',
					'',
					'baz'
				],
				[
					'<p>foo</p>',
					'',
					'<h2>bar</h2>',
					'',
					'<p>baz</p>'
				]
			],
			[
				[
					'* 0',
					' * 1',
					'  * 2',
					'   * 3',
					'    * 4',
					'     * 5',
					'      * 6',
					'       * 7',
					'        * 8',
					'         * 9'
				],
				[
					'<ul><li>0',
					' <ul><li>1</li>',
					'  <li>2</li>',
					'   <li>3</li>',
					'    <li>4',
					'     <ul><li>5</li>',
					'      <li>6</li>',
					'       <li>7</li>',
					'        <li>8',
					'         <ul><li>9</li></ul></li></ul></li></ul></li></ul>'
				]
			],
			[
				[
					'1. one',
					'2. two'
				],
				[
					'<ol><li>one</li>',
					'<li>two</li></ol>'
				]
			],
			[
				[
					'- one',
					'  - foo',
					'  - bar',
					'',
					'- two',
					'  - bar',
					'  - baz',
					'',
					'- three'
				],
				[
					'<ul><li><p>one</p>',
					'  <ul><li>foo</li>',
					'  <li>bar</li></ul></li>',
					'',
					'<li><p>two</p>',
					'  <ul><li>bar</li>',
					'  <li>baz</li></ul></li>',
					'',
					'<li><p>three</p></li></ul>'
				],
			],
			[
				'[Link text](http://example.org)',
				'<p><a href="http://example.org">Link text</a></p>'
			],
			[
				'[Link text](http://example.org "Link title")',
				'<p><a href="http://example.org" title="Link title">Link text</a></p>'
			],
		]);
	}

	protected static function fixTests($tests)
	{
		foreach ($tests as &$test)
		{
			if (is_array($test[0]))
			{
				$test[0] = implode("\n", $test[0]);
			}

			if (is_array($test[1]))
			{
				$test[1] = implode("\n", $test[1]);
			}

			$test[] = [];
			$test[] = function ($configurator)
			{
				$configurator->addHTML5Rules();
			};
		}

		return $tests;
	}
}