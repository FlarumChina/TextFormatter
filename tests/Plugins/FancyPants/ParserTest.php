<?php

namespace s9e\TextFormatter\Tests\Plugins\FancyPants;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\FancyPants\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\FancyPants\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavaScriptRunner;
	use RenderingTestsRunner;

	public function getParsingTests()
	{
		return [
			[
				'...',
				'<r><FP char="…">...</FP></r>'
			],
			[
				'...',
				'<r><FOO char="…">...</FOO></r>',
				['tagName' => 'FOO']
			],
			[
				'...',
				'<r><FP bar="…">...</FP></r>',
				['attrName' => 'bar']
			],
			[
				"'Good morning, Frank,' greeted HAL.",
				'<r><FP char="‘">\'</FP>Good morning, Frank,<FP char="’">\'</FP> greeted HAL.</r>'
			],
			[
				"\"'Good morning, Frank,' greeted HAL.\" is how the book starts.",
				'<r><FP char="“">"</FP><FP char="‘">\'</FP>Good morning, Frank,<FP char="’">\'</FP> greeted HAL.<FP char="”">"</FP> is how the book starts.</r>'
			],
			[
				'"Good morning, Frank," greeted HAL.',
				'<r><FP char="“">"</FP>Good morning, Frank,<FP char="”">"</FP> greeted HAL.</r>'
			],
			[
				'\'"Good morning, Frank," greeted HAL.\' is how the book starts.',
				'<r><FP char="‘">\'</FP><FP char="“">"</FP>Good morning, Frank,<FP char="”">"</FP> greeted HAL.<FP char="’">\'</FP> is how the book starts.</r>'
			],
			[
				'Hello world...',
				'<r>Hello world<FP char="…">...</FP></r>'
			],
			[
				'foo--bar',
				'<r>foo<FP char="–">--</FP>bar</r>'
			],
			[
				'foo---bar',
				'<r>foo<FP char="—">---</FP>bar</r>'
			],
			[
				'(tm)',
				'<r><FP char="™">(tm)</FP></r>'
			],
			[
				'(TM)',
				'<r><FP char="™">(TM)</FP></r>'
			],
			[
				'(c)',
				'<r><FP char="©">(c)</FP></r>'
			],
			[
				'(C)',
				'<r><FP char="©">(C)</FP></r>'
			],
			[
				'(r)',
				'<r><FP char="®">(r)</FP></r>'
			],
			[
				'(R)',
				'<r><FP char="®">(R)</FP></r>'
			],
			[
				"'Twas the night. 'Twas the night before Christmas.",
				'<r><FP char="’">\'</FP>Twas the night. <FP char="’">\'</FP>Twas the night before Christmas.</r>'
			],
			[
				"Say. 'Twas the night before Christmas.",
				'<r>Say. <FP char="’">\'</FP>Twas the night before Christmas.</r>'
			],
			[
				"Occam's razor",
				'<r>Occam<FP char="’">\'</FP>s razor</r>'
			],
			[
				"Ridin' dirty",
				'<r>Ridin<FP char="’">\'</FP> dirty</r>'
			],
			[
				"Get rich or die tryin'",
				'<r>Get rich or die tryin<FP char="’">\'</FP></r>'
			],
			[
				"Get rich or die tryin', yo.",
				'<r>Get rich or die tryin<FP char="’">\'</FP>, yo.</r>'
			],
			[
				"'88 was the year. '88 was the year indeed.",
				'<r><FP char="’">\'</FP>88 was the year. <FP char="’">\'</FP>88 was the year indeed.</r>'
			],
			[
				"'88 bottles of beer on the wall'",
				'<r><FP char="‘">\'</FP>88 bottles of beer on the wall<FP char="’">\'</FP></r>'
			],
			[
				"1950's",
				'<r>1950<FP char="’">\'</FP>s</r>'
			],
			[
				"I am 7' tall",
				'<r>I am 7<FP char="′">\'</FP> tall</r>'
			],
			[
				'12" vinyl',
				'<r>12<FP char="″">"</FP> vinyl</r>'
			],
			[
				'3x3',
				'<r>3<FP char="×">x</FP>3</r>'
			],
			[
				'3 x 3',
				'<r>3 <FP char="×">x</FP> 3</r>'
			],
			[
				'3" x 3"',
				'<r>3<FP char="″">"</FP> <FP char="×">x</FP> 3<FP char="″">"</FP></r>'
			],
			[
				'3"x3"',
				'<r>3<FP char="″">"</FP><FP char="×">x</FP>3<FP char="″">"</FP></r>'
			],
			[
				"3' x 3'",
				'<r>3<FP char="′">\'</FP> <FP char="×">x</FP> 3<FP char="′">\'</FP></r>'
			],
			[
				"3'x3'",
				'<r>3<FP char="′">\'</FP><FP char="×">x</FP>3<FP char="′">\'</FP></r>'
			],
			[
				"O'Connor's pants",
				'<r>O<FP char="’">\'</FP>Connor<FP char="’">\'</FP>s pants</r>'
			]
		];
	}

	public function getRenderingTests()
	{
		return [
			[
				'...',
				'…'
			],
			[
				'...',
				'…',
				['tagName' => 'FOO']
			],
			[
				'...',
				'…',
				['attrName' => 'bar']
			],
			[
				"'Good morning, Frank,' greeted HAL.",
				'‘Good morning, Frank,’ greeted HAL.'
			],
			[
				"\"'Good morning, Frank,' greeted HAL.\" is how the book starts.",
				'“‘Good morning, Frank,’ greeted HAL.” is how the book starts.'
			],
			[
				'"Good morning, Frank," greeted HAL.',
				'“Good morning, Frank,” greeted HAL.'
			],
			[
				'\'"Good morning, Frank," greeted HAL.\' is how the book starts.',
				'‘“Good morning, Frank,” greeted HAL.’ is how the book starts.'
			],
			[
				'Hello world...',
				'Hello world…'
			],
			[
				'foo--bar',
				'foo–bar'
			],
			[
				'foo---bar',
				'foo—bar'
			],
			[
				'(tm)',
				'™'
			],
			[
				'(TM)',
				'™'
			],
			[
				'(c)',
				'©'
			],
			[
				'(C)',
				'©'
			],
			[
				'(r)',
				'®'
			],
			[
				'(R)',
				'®'
			],
			[
				"'Twas the night. 'Twas the night before Christmas.",
				'’Twas the night. ’Twas the night before Christmas.'
			],
			[
				"Say. 'Twas the night before Christmas.",
				'Say. ’Twas the night before Christmas.'
			],
			[
				"Occam's razor",
				'Occam’s razor'
			],
			[
				"Ridin' dirty",
				'Ridin’ dirty'
			],
			[
				"Get rich or die tryin'",
				'Get rich or die tryin’'
			],
			[
				"Get rich or die tryin', yo.",
				'Get rich or die tryin’, yo.'
			],
			[
				"'88 was the year. '88 was the year indeed.",
				'’88 was the year. ’88 was the year indeed.'
			],
			[
				"'88 bottles of beer on the wall'",
				'‘88 bottles of beer on the wall’'
			],
			[
				"1950's",
				"1950’s"
			],
			[
				"I am 7' tall",
				"I am 7′ tall"
			],
			[
				'12" vinyl',
				'12″ vinyl'
			],
			[
				'3x3',
				'3×3'
			],
			[
				'3 x 3',
				'3 × 3'
			],
			[
				'3" x 3"',
				'3″ × 3″'
			],
			[
				"O'Connor's pants",
				'O’Connor’s pants'
			]
		];
	}
}