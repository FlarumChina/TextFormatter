<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Parser\Logger;
use s9e\TextFormatter\Parser\Tag;

class Parser
{
	/**#@+
	* Boolean rules bitfield
	*/
	const RULE_AUTO_CLOSE        = 1 << 0;
	const RULE_AUTO_REOPEN       = 1 << 1;
	const RULE_BREAK_PARAGRAPH   = 1 << 2;
	const RULE_CREATE_PARAGRAPHS = 1 << 3;
	const RULE_DISABLE_AUTO_BR   = 1 << 4;
	const RULE_ENABLE_AUTO_BR    = 1 << 5;
	const RULE_IGNORE_TAGS       = 1 << 6;
	const RULE_IGNORE_TEXT       = 1 << 7;
	const RULE_IS_TRANSPARENT    = 1 << 8;
	const RULE_PREVENT_BR        = 1 << 9;
	const RULE_SUSPEND_AUTO_BR   = 1 << 10;
	const RULE_TRIM_WHITESPACE   = 1 << 11;
	/**#@-*/

	/**
	* Bitwise disjunction of rules related to automatic line breaks
	*/
	const RULES_AUTO_LINEBREAKS = self::RULE_DISABLE_AUTO_BR | self::RULE_ENABLE_AUTO_BR | self::RULE_SUSPEND_AUTO_BR;

	/**
	* Bitwise disjunction of rules that are inherited by subcontexts
	*/
	const RULES_INHERITANCE = self::RULE_ENABLE_AUTO_BR;

	/**
	* All the characters that are considered whitespace
	*/
	const WHITESPACE = " \n\t";

	/**
	* @var array Number of open tags for each tag name
	*/
	protected $cntOpen;

	/**
	* @var array Number of times each tag has been used
	*/
	protected $cntTotal;

	/**
	* @var array Current context
	*/
	protected $context;

	/**
	* @var integer How hard the parser has worked on fixing bad markup so far
	*/
	protected $currentFixingCost;

	/**
	* @var Tag Current tag being processed
	*/
	protected $currentTag;

	/**
	* @var bool Whether the output contains "rich" tags, IOW any tag that is not <p> or <br/>
	*/
	protected $isRich;

	/**
	* @var Logger This parser's logger
	*/
	protected $logger;

	/**
	* @var integer How hard the parser should work on fixing bad markup
	*/
	public $maxFixingCost = 1000;

	/**
	* @var array Associative array of namespace prefixes in use in document (prefixes used as key)
	*/
	protected $namespaces;

	/**
	* @var array Stack of open tags (instances of Tag)
	*/
	protected $openTags;

	/**
	* @var string This parser's output
	*/
	protected $output;

	/**
	* @var integer Position of the cursor in the original text
	*/
	protected $pos;

	/**
	* @var array Array of callbacks, using plugin names as keys
	*/
	protected $pluginParsers = [];

	/**
	* @var array Associative array of [pluginName => pluginConfig]
	*/
	protected $pluginsConfig;

	/**
	* @var array Variables registered for use in filters
	*/
	public $registeredVars = [];

	/**
	* @var array Root context, used at the root of the document
	*/
	protected $rootContext;

	/**
	* @var array Tags' config
	*/
	protected $tagsConfig;

	/**
	* @var array Tag storage
	*/
	protected $tagStack;

	/**
	* @var bool Whether the tags in the stack are sorted
	*/
	protected $tagStackIsSorted;

	/**
	* @var string Text being parsed
	*/
	protected $text;

	/**
	* @var integer Length of the text being parsed
	*/
	protected $textLen;

	/**
	* @var integer Counter incremented everytime the parser is reset. Used to as a canary to detect
	*              whether the parser was reset during execution
	*/
	protected $uid = 0;

	/**
	* @var integer Position before which we output text verbatim, without paragraphs or linebreaks
	*/
	protected $wsPos;

	/**
	* Constructor
	*/
	public function __construct(array $config)
	{
		$this->pluginsConfig  = $config['plugins'];
		$this->registeredVars = $config['registeredVars'];
		$this->rootContext    = $config['rootContext'];
		$this->tagsConfig     = $config['tags'];

		$this->__wakeup();
	}

	/**
	* Serializer
	*
	* Returns the properties that need to persist through serialization.
	*
	* NOTE: using __sleep() is preferable to implementing Serializable because it leaves the choice
	* of the serializer to the user (e.g. igbinary)
	*
	* @return array
	*/
	public function __sleep()
	{
		return ['pluginsConfig', 'registeredVars', 'rootContext', 'tagsConfig'];
	}

	/**
	* Unserializer
	*
	* @return void
	*/
	public function __wakeup()
	{
		$this->logger = new Logger;
	}

	/**
	* Reset the parser for a new parsing
	*
	* @param  string $text Text to be parsed
	* @return void
	*/
	protected function reset($text)
	{
		// Normalize CR/CRLF to LF, remove control characters that aren't allowed in XML
		$text = preg_replace('/\\r\\n?/', "\n", $text);
		$text = preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]+/S', '', $text);

		// Clear the logs
		$this->logger->clear();

		// Initialize the rest
		$this->currentFixingCost = 0;
		$this->isRich     = false;
		$this->namespaces = [];
		$this->output     = '';
		$this->text       = $text;
		$this->textLen    = strlen($text);
		$this->tagStack   = [];
		$this->tagStackIsSorted = true;
		$this->wsPos      = 0;

		// Bump the UID
		++$this->uid;
	}

	/**
	* Set a tag's option
	*
	* This method ensures that the tag's config is a value and not a reference, to prevent
	* potential side-effects. References contained *inside* the tag's config are left untouched
	*
	* @param  string $tagName     Tag's name
	* @param  string $optionName  Option's name
	* @param  mixed  $optionValue Option's value
	* @return void
	*/
	protected function setTagOption($tagName, $optionName, $optionValue)
	{
		if (isset($this->tagsConfig[$tagName]))
		{
			// Copy the tag's config and remove it. That will destroy the reference
			$tagConfig = $this->tagsConfig[$tagName];
			unset($this->tagsConfig[$tagName]);

			// Set the new value and replace the tag's config
			$tagConfig[$optionName]     = $optionValue;
			$this->tagsConfig[$tagName] = $tagConfig;
		}
	}

	//==========================================================================
	// Public API
	//==========================================================================

	/**
	* Disable a tag
	*
	* @param  string $tagName Name of the tag
	* @return void
	*/
	public function disableTag($tagName)
	{
		$this->setTagOption($tagName, 'isDisabled', true);
	}

	/**
	* Enable a tag
	*
	* @param  string $tagName Name of the tag
	* @return void
	*/
	public function enableTag($tagName)
	{
		if (isset($this->tagsConfig[$tagName]))
		{
			unset($this->tagsConfig[$tagName]['isDisabled']);
		}
	}

	/**
	* Get this parser's Logger instance
	*
	* @return Logger
	*/
	public function getLogger()
	{
		return $this->logger;
	}

	/**
	* Return the last text parsed
	*
	* This method returns the normalized text, which may be slightly different from the original
	* text in that EOLs are normalized to LF and other control codes are stripped. This method is
	* meant to be used in support of processing log entries, which contain offsets based on the
	* normalized text
	*
	* @see Parser::reset()
	*
	* @return string
	*/
	public function getText()
	{
		return $this->text;
	}

	/**
	* Parse a text
	*
	* @param  string $text Text to parse
	* @return string       XML representation
	*/
	public function parse($text)
	{
		// Reset the parser and save the uid
		$this->reset($text);
		$uid = $this->uid;

		// Do the heavy lifting
		$this->executePluginParsers();
		$this->processTags();

		// Check the uid in case a plugin or a filter reset the parser mid-execution
		if ($this->uid !== $uid)
		{
			throw new RuntimeException('The parser has been reset during execution');
		}

		return $this->output;
	}

	/**
	* Change a tag's tagLimit
	*
	* NOTE: the default tagLimit should generally be set during configuration instead
	*
	* @param  string  $tagName  The tag's name, in UPPERCASE
	* @param  integer $tagLimit
	* @return void
	*/
	public function setTagLimit($tagName, $tagLimit)
	{
		$this->setTagOption($tagName, 'tagLimit', $tagLimit);
	}

	/**
	* Change a tag's nestingLimit
	*
	* NOTE: the default nestingLimit should generally be set during configuration instead
	*
	* @param  string  $tagName      The tag's name, in UPPERCASE
	* @param  integer $nestingLimit
	* @return void
	*/
	public function setNestingLimit($tagName, $nestingLimit)
	{
		$this->setTagOption($tagName, 'nestingLimit', $nestingLimit);
	}

	//==========================================================================
	// Filter processing
	//==========================================================================

	/**
	* Execute all the attribute preprocessors of given tag
	*
	* @private
	*
	* @param  Tag   $tag       Source tag
	* @param  array $tagConfig Tag's config
	* @return bool             Unconditionally TRUE
	*/
	public static function executeAttributePreprocessors(Tag $tag, array $tagConfig)
	{
		if (!empty($tagConfig['attributePreprocessors']))
		{
			foreach ($tagConfig['attributePreprocessors'] as list($attrName, $regexp))
			{
				if (!$tag->hasAttribute($attrName))
				{
					continue;
				}

				$attrValue = $tag->getAttribute($attrName);

				// If the regexp matches, we add the captured attributes
				if (preg_match($regexp, $attrValue, $m))
				{
					// Set the target attributes
					foreach ($m as $targetName => $targetValue)
					{
						// Skip numeric captures and empty captures
						if (is_numeric($targetName) || $targetValue === '')
						{
							continue;
						}

						// Attribute preprocessors cannot overwrite other attributes but they can
						// overwrite themselves
						if ($targetName === $attrName || !$tag->hasAttribute($targetName))
						{
							$tag->setAttribute($targetName, $targetValue);
						}
					}
				}
			}
		}

		return true;
	}

	/**
	* Execute a filter
	*
	* @see s9e\TextFormatter\Configurator\Items\ProgrammableCallback
	*
	* @param  array $filter Programmed callback
	* @param  array $vars   Variables to be used when executing the callback
	* @return mixed         Whatever the callback returns
	*/
	protected static function executeFilter(array $filter, array $vars)
	{
		$callback = $filter['callback'];
		$params   = (isset($filter['params'])) ? $filter['params'] : [];

		$args = [];
		foreach ($params as $k => $v)
		{
			if (is_numeric($k))
			{
				// By-value param
				$args[] = $v;
			}
			elseif (isset($vars[$k]))
			{
				// By-name param using a supplied var
				$args[] = $vars[$k];
			}
			elseif (isset($vars['registeredVars'][$k]))
			{
				// By-name param using a registered var
				$args[] = $vars['registeredVars'][$k];
			}
			else
			{
				// Unknown param
				$args[] = null;
			}
		}

		return call_user_func_array($callback, $args);
	}

	/**
	* Filter the attributes of given tag
	*
	* @private
	*
	* @param  Tag    $tag            Tag being checked
	* @param  array  $tagConfig      Tag's config
	* @param  array  $registeredVars Array of registered vars for use in attribute filters
	* @param  Logger $logger         This parser's Logger instance
	* @return bool                   Whether the whole attribute set is valid
	*/
	public static function filterAttributes(Tag $tag, array $tagConfig, array $registeredVars, Logger $logger)
	{
		if (empty($tagConfig['attributes']))
		{
			$tag->setAttributes([]);

			return true;
		}

		// Generate values for attributes with a generator set
		foreach ($tagConfig['attributes'] as $attrName => $attrConfig)
		{
			if (isset($attrConfig['generator']))
			{
				$tag->setAttribute(
					$attrName,
					self::executeFilter(
						$attrConfig['generator'],
						[
							'attrName'       => $attrName,
							'logger'         => $logger,
							'registeredVars' => $registeredVars
						]
					)
				);
			}
		}

		// Filter and remove invalid attributes
		foreach ($tag->getAttributes() as $attrName => $attrValue)
		{
			// Test whether this attribute exists and remove it if it doesn't
			if (!isset($tagConfig['attributes'][$attrName]))
			{
				$tag->removeAttribute($attrName);
				continue;
			}

			$attrConfig = $tagConfig['attributes'][$attrName];

			// Test whether this attribute has a filterChain
			if (!isset($attrConfig['filterChain']))
			{
				continue;
			}

			// Record the name of the attribute being filtered into the logger
			$logger->setAttribute($attrName);

			foreach ($attrConfig['filterChain'] as $filter)
			{
				$attrValue = self::executeFilter(
					$filter,
					[
						'attrName'       => $attrName,
						'attrValue'      => $attrValue,
						'logger'         => $logger,
						'registeredVars' => $registeredVars
					]
				);

				if ($attrValue === false)
				{
					$tag->removeAttribute($attrName);
					break;
				}
			}

			// Update the attribute value if it's valid
			if ($attrValue !== false)
			{
				$tag->setAttribute($attrName, $attrValue);
			}

			// Remove the attribute's name from the logger
			$logger->unsetAttribute();
		}

		// Iterate over the attribute definitions to handle missing attributes
		foreach ($tagConfig['attributes'] as $attrName => $attrConfig)
		{
			// Test whether this attribute is missing
			if (!$tag->hasAttribute($attrName))
			{
				if (isset($attrConfig['defaultValue']))
				{
					// Use the attribute's default value
					$tag->setAttribute($attrName, $attrConfig['defaultValue']);
				}
				elseif (!empty($attrConfig['required']))
				{
					// This attribute is missing, has no default value and is required, which means
					// the attribute set is invalid
					return false;
				}
			}
		}

		return true;
	}

	/**
	* Execute given tag's filterChain
	*
	* @param  Tag  $tag Tag to filter
	* @return bool      Whether the tag is valid
	*/
	protected function filterTag(Tag $tag)
	{
		$tagName   = $tag->getName();
		$tagConfig = $this->tagsConfig[$tagName];
		$isValid   = true;

		if (!empty($tagConfig['filterChain']))
		{
			// Record the tag being processed into the logger it can be added to the context of
			// messages logged during the execution
			$this->logger->setTag($tag);

			// Prepare the variables that are accessible to filters
			$vars = [
				'logger'         => $this->logger,
				'openTags'       => $this->openTags,
				'parser'         => $this,
				'registeredVars' => $this->registeredVars,
				'tag'            => $tag,
				'tagConfig'      => $tagConfig
			];

			foreach ($tagConfig['filterChain'] as $filter)
			{
				if (!self::executeFilter($filter, $vars))
				{
					$isValid = false;
					break;
				}
			}

			// Remove the tag from the logger
			$this->logger->unsetTag();
		}

		return $isValid;
	}

	//==========================================================================
	// Output handling
	//==========================================================================

	/**
	* Finalize the output by appending the rest of the unprocessed text and create the root node
	*
	* @return void
	*/
	protected function finalizeOutput()
	{
		// Output the rest of the text and close the last paragraph
		$this->outputText($this->textLen, 0, true);

		// Remove empty tag pairs, e.g. <I><U></U></I> as well as empty paragraphs
		do
		{
			$this->output = preg_replace(
				'#<([\\w:]+)[^>]*></\\1>#',
				'',
				$this->output,
				-1,
				$cnt
			);
		}
		while ($cnt);

		// Merge consecutive <i> tags
		if (strpos($this->output, '</i><i>') !== false)
		{
			$this->output = str_replace('</i><i>', '', $this->output);
		}

		// Use a <r> root if the text is rich, or <t> for plain text (including <p></p> and <br/>)
		$tagName = ($this->isRich) ? 'r' : 't';

		// Prepare the root node with all the namespace declarations
		$tmp = '<' . $tagName;
		foreach (array_keys($this->namespaces) as $prefix)
		{
			$tmp .= ' xmlns:' . $prefix . '="urn:s9e:TextFormatter:' . $prefix . '"';
		}

		$this->output = $tmp . '>' . $this->output . '</' . $tagName . '>';
	}

	/**
	* Append a tag to the output
	*
	* @param  Tag  $tag Tag to append
	* @return void
	*/
	protected function outputTag(Tag $tag)
	{
		$this->isRich = true;

		$tagName  = $tag->getName();
		$tagPos   = $tag->getPos();
		$tagLen   = $tag->getLen();
		$tagFlags = $tag->getFlags();

		if ($tagFlags & self::RULE_TRIM_WHITESPACE)
		{
			$skipBefore = ($tag->isStartTag()) ? 2 : 1;
			$skipAfter  = ($tag->isEndTag())   ? 2 : 1;
		}
		else
		{
			$skipBefore = $skipAfter = 0;
		}

		// Current paragraph must end before the tag if:
		//  - the tag is a start (or self-closing) tag and it breaks paragraphs, or
		//  - the tag is an end tag (but not self-closing)
		$closeParagraph = false;
		if ($tag->isStartTag())
		{
			if ($tagFlags & self::RULE_BREAK_PARAGRAPH)
			{
				$closeParagraph = true;
			}
		}
		else
		{
			$closeParagraph = true;
		}

		// Let the cursor catch up with this tag's position
		$this->outputText($tagPos, $skipBefore, $closeParagraph);

		// Capture the text consumed by the tag
		$tagText = ($tagLen)
		         ? htmlspecialchars(substr($this->text, $tagPos, $tagLen), ENT_NOQUOTES, 'UTF-8')
		         : '';

		// Output current tag
		if ($tag->isStartTag())
		{
			// Handle paragraphs before opening the tag
			if (!($tagFlags & self::RULE_BREAK_PARAGRAPH))
			{
				$this->outputParagraphStart($tagPos);
			}

			// Record this tag's namespace, if applicable
			$colonPos = strpos($tagName, ':');
			if ($colonPos)
			{
				$this->namespaces[substr($tagName, 0, $colonPos)] = 0;
			}

			// Open the start tag and add its attributes, but don't close the tag
			$this->output .= '<' . $tagName;

			// We output the attributes in lexical order. Helps canonicalizing the output and could
			// prove useful someday
			$attributes = $tag->getAttributes();
			ksort($attributes);

			foreach ($attributes as $attrName => $attrValue)
			{
				$this->output .= ' ' . $attrName . '="' . htmlspecialchars($attrValue, ENT_COMPAT, 'UTF-8') . '"';
			}

			if ($tag->isSelfClosingTag())
			{
				if ($tagLen)
				{
					$this->output .= '>' . $tagText . '</' . $tagName . '>';
				}
				else
				{
					$this->output .= '/>';
				}
			}
			elseif ($tagLen)
			{
				$this->output .= '><s>' . $tagText . '</s>';
			}
			else
			{
				$this->output .= '>';
			}
		}
		else
		{
			if ($tagLen)
			{
				$this->output .= '<e>' . $tagText . '</e>';
			}

			$this->output .= '</' . $tagName . '>';
		}

		// Move the cursor past the tag
		$this->pos = $tagPos + $tagLen;

		// Skip newlines (no other whitespace) after this tag
		$this->wsPos = $this->pos;
		while ($skipAfter && $this->wsPos < $this->textLen && $this->text[$this->wsPos] === "\n")
		{
			// Decrement the number of lines to skip
			--$skipAfter;

			// Move the cursor past the newline
			++$this->wsPos;
		}
	}

	/**
	* Output the text between the cursor's position (included) and given position (not included)
	*
	* @param  integer $catchupPos     Position we're catching up to
	* @param  integer $maxLines       Maximum number of lines to ignore at the end of the text
	* @param  bool    $closeParagraph Whether to close the paragraph at the end, if applicable
	* @return void
	*/
	protected function outputText($catchupPos, $maxLines, $closeParagraph)
	{
		if ($closeParagraph)
		{
			if (!($this->context['flags'] & self::RULE_CREATE_PARAGRAPHS))
			{
				$closeParagraph = false;
			}
			else
			{
				// Ignore any number of lines at the end if we're closing a paragraph
				$maxLines = -1;
			}
		}

		if ($this->pos >= $catchupPos)
		{
			// We're already there, close the paragraph if applicable and return
			if ($closeParagraph)
			{
				$this->outputParagraphEnd();
			}

			return;
		}

		// Skip over previously identified whitespace if applicable
		if ($this->wsPos > $this->pos)
		{
			$skipPos       = min($catchupPos, $this->wsPos);
			$this->output .= substr($this->text, $this->pos, $skipPos - $this->pos);
			$this->pos     = $skipPos;

			if ($this->pos >= $catchupPos)
			{
				// Skipped everything. Close the paragraph if applicable and return
				if ($closeParagraph)
				{
					$this->outputParagraphEnd();
				}

				return;
			}
		}

		// Test whether we're even supposed to output anything
		if ($this->context['flags'] & self::RULE_IGNORE_TEXT)
		{
			$catchupLen  = $catchupPos - $this->pos;
			$catchupText = substr($this->text, $this->pos, $catchupLen);

			// If the catchup text is not entirely composed of whitespace, we put it inside ignore
			// tags
			if (strspn($catchupText, " \n\t") < $catchupLen)
			{
				$catchupText = '<i>' . $catchupText . '</i>';
			}

			$this->output .= $catchupText;
			$this->pos = $catchupPos;

			if ($closeParagraph)
			{
				$this->outputParagraphEnd();
			}

			return;
		}

		// Compute the amount of text to ignore at the end of the output
		$ignorePos = $catchupPos;
		$ignoreLen = 0;

		// Ignore as many lines (including whitespace) as specified
		while ($maxLines && --$ignorePos >= $this->pos)
		{
			$c = $this->text[$ignorePos];
			if (strpos(self::WHITESPACE, $c) === false)
			{
				break;
			}

			if ($c === "\n")
			{
				--$maxLines;
			}

			++$ignoreLen;
		}

		// Adjust $catchupPos to ignore the text at the end
		$catchupPos -= $ignoreLen;

		// Break down the text in paragraphs if applicable
		if ($this->context['flags'] & self::RULE_CREATE_PARAGRAPHS)
		{
			if (!$this->context['inParagraph'])
			{
				$this->outputWhitespace($catchupPos);

				if ($catchupPos > $this->pos)
				{
					$this->outputParagraphStart($catchupPos);
				}
			}

			// Look for a paragraph break in this text
			$pbPos = strpos($this->text, "\n\n", $this->pos);

			while ($pbPos !== false && $pbPos < $catchupPos)
			{
				$this->outputText($pbPos, 0, true);
				$this->outputParagraphStart($catchupPos);

				$pbPos = strpos($this->text, "\n\n", $this->pos);
			}
		}

		// Capture, escape and output the text
		if ($catchupPos > $this->pos)
		{
			$catchupText = htmlspecialchars(
				substr($this->text, $this->pos, $catchupPos - $this->pos),
				ENT_NOQUOTES,
				'UTF-8'
			);

			// Format line breaks if applicable
			if (($this->context['flags'] & self::RULES_AUTO_LINEBREAKS) === self::RULE_ENABLE_AUTO_BR)
			{
				$catchupText = str_replace("\n", "<br/>\n", $catchupText);
			}

			$this->output .= $catchupText;
		}

		// Close the paragraph if applicable
		if ($closeParagraph)
		{
			$this->outputParagraphEnd();
		}

		// Add the ignored text if applicable
		if ($ignoreLen)
		{
			$this->output .= substr($this->text, $catchupPos, $ignoreLen);
		}

		// Move the cursor past the text
		$this->pos = $catchupPos + $ignoreLen;
	}

	/**
	* Output a linebreak tag
	*
	* @param  Tag  $tag
	* @return void
	*/
	protected function outputBrTag(Tag $tag)
	{
		$this->outputText($tag->getPos(), 0, false);
		$this->output .= '<br/>';
	}

	/**
	* Output an ignore tag
	*
	* @param  Tag  $tag
	* @return void
	*/
	protected function outputIgnoreTag(Tag $tag)
	{
		$tagPos = $tag->getPos();
		$tagLen = $tag->getLen();

		// Capture the text to ignore
		$ignoreText = substr($this->text, $tagPos, $tagLen);

		// Catch up with the tag's position then output the tag
		$this->outputText($tagPos, 0, false);
		$this->output .= '<i>' . htmlspecialchars($ignoreText, ENT_NOQUOTES, 'UTF-8') . '</i>';
		$this->isRich = true;

		// Move the cursor past this tag
		$this->pos = $tagPos + $tagLen;
	}

	/**
	* Start a paragraph between current position and given position, if applicable
	*
	* @param  integer $maxPos Rightmost position at which the paragraph can be opened
	* @return void
	*/
	protected function outputParagraphStart($maxPos)
	{
		// Do nothing if we're already in a paragraph, or if we don't use paragraphs
		if ($this->context['inParagraph']
		 || !($this->context['flags'] & self::RULE_CREATE_PARAGRAPHS))
		{
			return;
		}

		// Output the whitespace between $this->pos and $maxPos if applicable
		$this->outputWhitespace($maxPos);

		// Open the paragraph, but only if it's not at the very end of the text
		if ($this->pos < $this->textLen)
		{
			$this->output .= '<p>';
			$this->context['inParagraph'] = true;
		}
	}

	/**
	* Close current paragraph at current position if applicable
	*
	* @return void
	*/
	protected function outputParagraphEnd()
	{
		// Do nothing if we're not in a paragraph
		if (!$this->context['inParagraph'])
		{
			return;
		}

		$this->output .= '</p>';
		$this->context['inParagraph'] = false;
	}

	/**
	* Skip as much whitespace after current position as possible
	*
	* @param  integer $maxPos Rightmost character to be skipped
	* @return void
	*/
	protected function outputWhitespace($maxPos)
	{
		if ($maxPos > $this->pos)
		{
			$spn = strspn($this->text, self::WHITESPACE, $this->pos, $maxPos - $this->pos);

			if ($spn)
			{
				$this->output .= substr($this->text, $this->pos, $spn);
				$this->pos += $spn;
			}
		}
	}

	//==========================================================================
	// Plugins handling
	//==========================================================================

	/**
	* Disable a plugin
	*
	* @param  string $pluginName Name of the plugin
	* @return void
	*/
	public function disablePlugin($pluginName)
	{
		if (isset($this->pluginsConfig[$pluginName]))
		{
			// Copy the plugin's config to remove the reference
			$pluginConfig = $this->pluginsConfig[$pluginName];
			unset($this->pluginsConfig[$pluginName]);

			// Update the value and replace the plugin's config
			$pluginConfig['isDisabled'] = true;
			$this->pluginsConfig[$pluginName] = $pluginConfig;
		}
	}

	/**
	* Enable a plugin
	*
	* @param  string $pluginName Name of the plugin
	* @return void
	*/
	public function enablePlugin($pluginName)
	{
		if (isset($this->pluginsConfig[$pluginName]))
		{
			$this->pluginsConfig[$pluginName]['isDisabled'] = false;
		}
	}

	/**
	* Execute all the plugins
	*
	* @return void
	*/
	protected function executePluginParsers()
	{
		foreach ($this->pluginsConfig as $pluginName => $pluginConfig)
		{
			if (!empty($pluginConfig['isDisabled']))
			{
				continue;
			}

			if (isset($pluginConfig['quickMatch'])
			 && strpos($this->text, $pluginConfig['quickMatch']) === false)
			{
				continue;
			}

			$matches = [];

			if (isset($pluginConfig['regexp']))
			{
				$cnt = preg_match_all(
					$pluginConfig['regexp'],
					$this->text,
					$matches,
					PREG_SET_ORDER | PREG_OFFSET_CAPTURE
				);

				if (!$cnt)
				{
					continue;
				}

				if ($cnt > $pluginConfig['regexpLimit'])
				{
					if ($pluginConfig['regexpLimitAction'] === 'abort')
					{
						throw new RuntimeException($pluginName . ' limit exceeded');
					}

					$matches = array_slice($matches, 0, $pluginConfig['regexpLimit']);

					$msg = 'Regexp limit exceeded. Only the allowed number of matches will be processed';
					$context = [
						'pluginName' => $pluginName,
						'limit'      => $pluginConfig['regexpLimit']
					];

					if ($pluginConfig['regexpLimitAction'] === 'warn')
					{
						$this->logger->warn($msg, $context);
					}
				}
			}

			// Cache a new instance of this plugin's parser if there isn't one already
			if (!isset($this->pluginParsers[$pluginName]))
			{
				$className = (isset($pluginConfig['className']))
				           ? $pluginConfig['className']
				           : 's9e\\TextFormatter\\Plugins\\' . $pluginName . '\\Parser';

				// Register the parser as a callback
				$this->pluginParsers[$pluginName] = [
					new $className($this, $pluginConfig),
					'parse'
				];
			}

			// Execute the plugin's parser, which will add tags via $this->addStartTag() and others
			call_user_func($this->pluginParsers[$pluginName], $this->text, $matches);
		}
	}

	/**
	* Register a parser
	*
	* Can be used to add a new parser with no plugin config, or pre-generate a parser for an
	* existing plugin
	*
	* @param  string   $pluginName
	* @param  callback $parser
	* @return void
	*/
	public function registerParser($pluginName, $parser)
	{
		if (!is_callable($parser))
		{
			throw new InvalidArgumentException('Argument 1 passed to ' . __METHOD__ . ' must be a valid callback');
		}

		// Create an empty config for this plugin to ensure it is executed
		if (!isset($this->pluginsConfig[$pluginName]))
		{
			$this->pluginsConfig[$pluginName] = [];
		}

		$this->pluginParsers[$pluginName] = $parser;
	}

	//==========================================================================
	// Rules handling
	//==========================================================================

	/**
	* Apply closeAncestor rules associated with given tag
	*
	* @param  Tag  $tag Tag
	* @return bool      Whether a new tag has been added
	*/
	protected function closeAncestor(Tag $tag)
	{
		if (!empty($this->openTags))
		{
			$tagName   = $tag->getName();
			$tagConfig = $this->tagsConfig[$tagName];

			if (!empty($tagConfig['rules']['closeAncestor']))
			{
				$i = count($this->openTags);

				while (--$i >= 0)
				{
					$ancestor     = $this->openTags[$i];
					$ancestorName = $ancestor->getName();

					if (isset($tagConfig['rules']['closeAncestor'][$ancestorName]))
					{
						// We have to close this ancestor. First we reinsert this tag...
						$this->tagStack[] = $tag;

						// ...then we add a new end tag for it
						$this->addMagicEndTag($ancestor, $tag->getPos());

						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	* Apply closeParent rules associated with given tag
	*
	* @param  Tag  $tag Tag
	* @return bool      Whether a new tag has been added
	*/
	protected function closeParent(Tag $tag)
	{
		if (!empty($this->openTags))
		{
			$tagName   = $tag->getName();
			$tagConfig = $this->tagsConfig[$tagName];

			if (!empty($tagConfig['rules']['closeParent']))
			{
				$parent     = end($this->openTags);
				$parentName = $parent->getName();

				if (isset($tagConfig['rules']['closeParent'][$parentName]))
				{
					// We have to close that parent. First we reinsert the tag...
					$this->tagStack[] = $tag;

					// ...then we add a new end tag for it
					$this->addMagicEndTag($parent, $tag->getPos());

					return true;
				}
			}
		}

		return false;
	}

	/**
	* Apply fosterParent rules associated with given tag
	*
	* NOTE: this rule has the potential for creating an unbounded loop, either if a tag tries to
	*       foster itself or two or more tags try to foster each other in a loop. We mitigate the
	*       risk by preventing a tag from creating a child of itself (the parent still gets closed)
	*       and by checking and increasing the currentFixingCost so that a loop of multiple tags
	*       do not run indefinitely. The default tagLimit and nestingLimit also serve to prevent the
	*       loop from running indefinitely
	*
	* @param  Tag  $tag Tag
	* @return bool      Whether a new tag has been added
	*/
	protected function fosterParent(Tag $tag)
	{
		if (!empty($this->openTags))
		{
			$tagName   = $tag->getName();
			$tagConfig = $this->tagsConfig[$tagName];

			if (!empty($tagConfig['rules']['fosterParent']))
			{
				$parent     = end($this->openTags);
				$parentName = $parent->getName();

				if (isset($tagConfig['rules']['fosterParent'][$parentName]))
				{
					if ($parentName !== $tagName && $this->currentFixingCost < $this->maxFixingCost)
					{
						// Add a 0-width copy of the parent tag right after this tag, and make it
						// depend on this tag
						$child = $this->addCopyTag($parent, $tag->getPos() + $tag->getLen(), 0);
						$tag->cascadeInvalidationTo($child);
					}

					++$this->currentFixingCost;

					// Reinsert current tag
					$this->tagStack[] = $tag;

					// And finally close its parent
					$this->addMagicEndTag($parent, $tag->getPos());

					return true;
				}
			}
		}

		return false;
	}

	/**
	* Apply requireAncestor rules associated with given tag
	*
	* @param  Tag  $tag Tag
	* @return bool      Whether this tag has an unfulfilled requireAncestor requirement
	*/
	protected function requireAncestor(Tag $tag)
	{
		$tagName   = $tag->getName();
		$tagConfig = $this->tagsConfig[$tagName];

		if (isset($tagConfig['rules']['requireAncestor']))
		{
			foreach ($tagConfig['rules']['requireAncestor'] as $ancestorName)
			{
				if (!empty($this->cntOpen[$ancestorName]))
				{
					return false;
				}
			}

			$this->logger->err('Tag requires an ancestor', [
				'requireAncestor' => implode(',', $tagConfig['rules']['requireAncestor']),
				'tag'             => $tag
			]);

			return true;
		}

		return false;
	}

	//==========================================================================
	// Tag processing
	//==========================================================================

	/**
	* Create and add an end tag for given start tag at given position
	*
	* @param  Tag     $startTag Start tag
	* @param  integer $tagPos   End tag's position (will be adjusted for whitespace if applicable)
	* @return void
	*/
	protected function addMagicEndTag(Tag $startTag, $tagPos)
	{
		$tagName = $startTag->getName();

		// Adjust the end tag's position if whitespace is to be minimized
		if ($startTag->getFlags() & self::RULE_TRIM_WHITESPACE)
		{
			$tagPos = $this->getMagicPos($tagPos);
		}

		// Add a 0-width end tag that is paired with the given start tag
		$this->addEndTag($tagName, $tagPos, 0)->pairWith($startTag);
	}

	/**
	* Compute the position of a magic end tag, adjusted for whitespace
	*
	* @param  integer $tagPos Rightmost possible position for the tag
	* @return integer
	*/
	protected function getMagicPos($tagPos)
	{
		// Back up from given position to the cursor's position until we find a character that
		// is not whitespace
		while ($tagPos > $this->pos && strpos(self::WHITESPACE, $this->text[$tagPos - 1]) !== false)
		{
			--$tagPos;
		}

		return $tagPos;
	}

	/**
	* Process all tags in the stack
	*
	* @return void
	*/
	protected function processTags()
	{
		// Reset some internal vars
		$this->pos       = 0;
		$this->cntOpen   = [];
		$this->cntTotal  = [];
		$this->openTags  = [];
		unset($this->currentTag);

		// Initialize the root context
		$this->context = $this->rootContext;
		$this->context['inParagraph'] = false;

		// Initialize the count tables
		foreach (array_keys($this->tagsConfig) as $tagName)
		{
			$this->cntOpen[$tagName]  = 0;
			$this->cntTotal[$tagName] = 0;
		}

		// Process the tag stack, close tags that were left open and repeat until done
		do
		{
			while (!empty($this->tagStack))
			{
				if (!$this->tagStackIsSorted)
				{
					$this->sortTags();
				}

				$this->currentTag = array_pop($this->tagStack);

				// Skip current tag if tags are disabled and current tag would not close the last
				// open tag and is not a special tag such as a line/paragraph break or an ignore tag
				if ($this->context['flags'] & self::RULE_IGNORE_TAGS)
				{
					if (!$this->currentTag->canClose(end($this->openTags))
					 && !$this->currentTag->isSystemTag())
					{
						continue;
					}
				}

				$this->processCurrentTag();
			}

			// Close tags that were left open
			foreach ($this->openTags as $startTag)
			{
				// NOTE: we add tags in hierarchical order (ancestors to descendants) but since
				//       the stack is processed in LIFO order, it means that tags get closed in
				//       the correct order, from descendants to ancestors
				$this->addMagicEndTag($startTag, $this->textLen);
			}
		}
		while (!empty($this->tagStack));

		// Finalize the document
		$this->finalizeOutput();
	}

	/**
	* Process current tag
	*
	* @return void
	*/
	protected function processCurrentTag()
	{
		if ($this->currentTag->isInvalid())
		{
			return;
		}

		$tagPos = $this->currentTag->getPos();
		$tagLen = $this->currentTag->getLen();

		// Test whether the cursor passed this tag's position already
		if ($this->pos > $tagPos)
		{
			// Test whether this tag is paired with a start tag and this tag is still open
			$startTag = $this->currentTag->getStartTag();

			if ($startTag && in_array($startTag, $this->openTags, true))
			{
				// Create an end tag that matches current tag's start tag, which consumes as much of
				// the same text as current tag and is paired with the same start tag
				$this->addEndTag(
					$startTag->getName(),
					$this->pos,
					max(0, $tagPos + $tagLen - $this->pos)
				)->pairWith($startTag);

				// Note that current tag is not invalidated, it's merely replaced
				return;
			}

			// If this is an ignore tag, try to ignore as much as the remaining text as possible
			if ($this->currentTag->isIgnoreTag())
			{
				$ignoreLen = $tagPos + $tagLen - $this->pos;

				if ($ignoreLen > 0)
				{
					// Create a new ignore tag and move on
					$this->addIgnoreTag($this->pos, $ignoreLen);

					return;
				}
			}

			// Skipped tags are invalidated
			$this->currentTag->invalidate();

			return;
		}

		if ($this->currentTag->isIgnoreTag())
		{
			$this->outputIgnoreTag($this->currentTag);
		}
		elseif ($this->currentTag->isBrTag())
		{
			// Output the tag if it's allowed, ignore it otherwise
			if (!($this->context['flags'] & self::RULE_PREVENT_BR))
			{
				$this->outputBrTag($this->currentTag);
			}
		}
		elseif ($this->currentTag->isParagraphBreak())
		{
			$this->outputText($this->currentTag->getPos(), 0, true);
		}
		elseif ($this->currentTag->isStartTag())
		{
			$this->processStartTag($this->currentTag);
		}
		else
		{
			$this->processEndTag($this->currentTag);
		}
	}

	/**
	* Process given start tag (including self-closing tags) at current position
	*
	* @param  Tag  $tag Start tag (including self-closing)
	* @return void
	*/
	protected function processStartTag(Tag $tag)
	{
		$tagName   = $tag->getName();
		$tagConfig = $this->tagsConfig[$tagName];

		// 1. Check that this tag has not reached its global limit tagLimit
		// 2. Execute this tag's filterChain, which will filter/validate its attributes
		// 3. Apply closeParent, closeAncestor and fosterParent rules
		// 4. Check for nestingLimit
		// 5. Apply requireAncestor rules
		//
		// This order ensures that the tag is valid and within the set limits before we attempt to
		// close parents or ancestors. We need to close ancestors before we can check for nesting
		// limits, whether this tag is allowed within current context (the context may change
		// as ancestors are closed) or whether the required ancestors are still there (they might
		// have been closed by a rule.)
		if ($this->cntTotal[$tagName] >= $tagConfig['tagLimit'])
		{
			$this->logger->err(
				'Tag limit exceeded',
				[
					'tag'      => $tag,
					'tagName'  => $tagName,
					'tagLimit' => $tagConfig['tagLimit']
				]
			);
			$tag->invalidate();

			return;
		}

		if (!$this->filterTag($tag))
		{
			$tag->invalidate();

			return;
		}

		if ($this->fosterParent($tag) || $this->closeParent($tag) || $this->closeAncestor($tag))
		{
			// This tag parent/ancestor needs to be closed, we just return (the tag is still valid)
			return;
		}

		if ($this->cntOpen[$tagName] >= $tagConfig['nestingLimit'])
		{
			$this->logger->err(
				'Nesting limit exceeded',
				[
					'tag'          => $tag,
					'tagName'      => $tagName,
					'nestingLimit' => $tagConfig['nestingLimit']
				]
			);
			$tag->invalidate();

			return;
		}

		if (!$this->tagIsAllowed($tagName))
		{
			$this->logger->warn(
				'Tag is not allowed in this context',
				[
					'tag'     => $tag,
					'tagName' => $tagName
				]
			);
			$tag->invalidate();

			return;
		}

		if ($this->requireAncestor($tag))
		{
			$tag->invalidate();

			return;
		}

		// If this tag has an autoClose rule and it's not paired with an end tag, we replace it
		// with a self-closing tag with the same properties
		if ($tag->getFlags() & self::RULE_AUTO_CLOSE
		 && !$tag->getEndTag())
		{
			$newTag = new Tag(Tag::SELF_CLOSING_TAG, $tagName, $tag->getPos(), $tag->getLen());
			$newTag->setAttributes($tag->getAttributes());
			$newTag->setFlags($tag->getFlags());

			$tag = $newTag;
		}

		// This tag is valid, output it and update the context
		$this->outputTag($tag);
		$this->pushContext($tag);
	}

	/**
	* Process given end tag at current position
	*
	* @param  Tag  $tag end tag
	* @return void
	*/
	protected function processEndTag(Tag $tag)
	{
		$tagName = $tag->getName();

		if (empty($this->cntOpen[$tagName]))
		{
			// This is an end tag with no start tag
			return;
		}

		/**
		* @var array List of tags need to be closed before given tag
		*/
		$closeTags = [];

		// Iterate through all open tags from last to first to find a match for our tag
		$i = count($this->openTags);
		while (--$i >= 0)
		{
			$openTag = $this->openTags[$i];

			if ($tag->canClose($openTag))
			{
				break;
			}

			if (++$this->currentFixingCost > $this->maxFixingCost)
			{
				throw new RuntimeException('Fixing cost exceeded');
			}

			$closeTags[] = $openTag;
		}

		if ($i < 0)
		{
			// Did not find a matching tag
			$this->logger->debug('Skipping end tag with no start tag', ['tag' => $tag]);

			return;
		}

		// Only reopen tags if we haven't exceeded our "fixing" budget
		$keepReopening = (bool) ($this->currentFixingCost < $this->maxFixingCost);

		// Iterate over tags that are being closed, output their end tag and collect tags to be
		// reopened
		$reopenTags = [];
		foreach ($closeTags as $openTag)
		{
			$openTagName = $openTag->getName();

			// Test whether this tag should be reopened automatically
			if ($keepReopening)
			{
				if ($openTag->getFlags() & self::RULE_AUTO_REOPEN)
				{
					$reopenTags[] = $openTag;
				}
				else
				{
					$keepReopening = false;
				}
			}

			// Find the earliest position we can close this open tag
			$tagPos = $tag->getPos();
			if ($openTag->getFlags() & self::RULE_TRIM_WHITESPACE)
			{
				$tagPos = $this->getMagicPos($tagPos);
			}

			// Output an end tag to close this start tag, then update the context
			$endTag = new Tag(Tag::END_TAG, $openTagName, $tagPos, 0);
			$endTag->setFlags($openTag->getFlags());
			$this->outputTag($endTag);
			$this->popContext();
		}

		// Output our tag, moving the cursor past it, then update the context
		$this->outputTag($tag);
		$this->popContext();

		// If our fixing budget allows it, peek at upcoming tags and remove end tags that would
		// close tags that are already being closed now. Also, filter our list of tags being
		// reopened by removing those that would immediately be closed
		if ($closeTags && $this->currentFixingCost < $this->maxFixingCost)
		{
			/**
			* @var integer Rightmost position of the portion of text to ignore
			*/
			$ignorePos = $this->pos;

			$i = count($this->tagStack);
			while (--$i >= 0 && ++$this->currentFixingCost < $this->maxFixingCost)
			{
				$upcomingTag = $this->tagStack[$i];

				// Test whether the upcoming tag is positioned at current "ignore" position and it's
				// strictly an end tag (not a start tag or a self-closing tag)
				if ($upcomingTag->getPos() > $ignorePos
				 || $upcomingTag->isStartTag())
				{
					break;
				}

				// Test whether this tag would close any of the tags we're about to reopen
				$j = count($closeTags);

				while (--$j >= 0 && ++$this->currentFixingCost < $this->maxFixingCost)
				{
					if ($upcomingTag->canClose($closeTags[$j]))
					{
						// Remove the tag from the lists and reset the keys
						array_splice($closeTags, $j, 1);

						if (isset($reopenTags[$j]))
						{
							array_splice($reopenTags, $j, 1);
						}

						// Extend the ignored text to cover this tag
						$ignorePos = max(
							$ignorePos,
							$upcomingTag->getPos() + $upcomingTag->getLen()
						);

						break;
					}
				}
			}

			if ($ignorePos > $this->pos)
			{
				/**
				* @todo have a method that takes (pos,len) rather than a Tag
				*/
				$this->outputIgnoreTag(new Tag(Tag::SELF_CLOSING_TAG, 'i', $this->pos, $ignorePos - $this->pos));
			}
		}

		// Re-add tags that need to be reopened, at current cursor position
		foreach ($reopenTags as $startTag)
		{
			$newTag = $this->addCopyTag($startTag, $this->pos, 0);

			// Re-pair the new tag
			$endTag = $startTag->getEndTag();
			if ($endTag)
			{
				$newTag->pairWith($endTag);
			}
		}
	}

	/**
	* Update counters and replace current context with its parent context
	*
	* @return void
	*/
	protected function popContext()
	{
		$tag = array_pop($this->openTags);
		--$this->cntOpen[$tag->getName()];
		$this->context = $this->context['parentContext'];
	}

	/**
	* Update counters and replace current context with a new context based on given tag
	*
	* If given tag is a self-closing tag, the context won't change
	*
	* @param  Tag  $tag Start tag (including self-closing)
	* @return void
	*/
	protected function pushContext(Tag $tag)
	{
		$tagName   = $tag->getName();
		$tagFlags  = $tag->getFlags();
		$tagConfig = $this->tagsConfig[$tagName];

		++$this->cntTotal[$tagName];

		// If this is a self-closing tag, we don't need to do anything else; The context remains the
		// same
		if ($tag->isSelfClosingTag())
		{
			return;
		}

		++$this->cntOpen[$tagName];
		$this->openTags[] = $tag;

		$allowedChildren = $tagConfig['allowedChildren'];

		// If the tag is transparent, we restrict its allowed children to the same set as its
		// parent, minus this tag's own disallowed children
		if ($tagFlags & self::RULE_IS_TRANSPARENT)
		{
			$allowedChildren &= $this->context['allowedChildren'];
		}

		// The allowedDescendants bitfield is restricted by this tag's
		$allowedDescendants = $this->context['allowedDescendants']
		                    & $tagConfig['allowedDescendants'];

		// Ensure that disallowed descendants are not allowed as children
		$allowedChildren &= $allowedDescendants;

		// Use this tag's flags as a base for this context
		$flags = $tagFlags;

		// Add inherited rules
		$flags |= $this->context['flags'] & self::RULES_INHERITANCE;

		// RULE_DISABLE_AUTO_BR turns off RULE_ENABLE_AUTO_BR
		if ($flags & self::RULE_DISABLE_AUTO_BR)
		{
			$flags &= ~self::RULE_ENABLE_AUTO_BR;
		}

		$this->context = [
			'allowedChildren'    => $allowedChildren,
			'allowedDescendants' => $allowedDescendants,
			'flags'              => $flags,
			'inParagraph'        => false,
			'parentContext'      => $this->context
		];
	}

	/**
	* Return whether given tag is allowed in current context
	*
	* @param  string $tagName
	* @return bool
	*/
	protected function tagIsAllowed($tagName)
	{
		$n = $this->tagsConfig[$tagName]['bitNumber'];

		return (bool) (ord($this->context['allowedChildren'][$n >> 3]) & (1 << ($n & 7)));
	}

	//==========================================================================
	// Tag stack
	//==========================================================================

	/**
	* Add a start tag
	*
	* @param  string  $name Name of the tag
	* @param  integer $pos  Position of the tag in the text
	* @param  integer $len  Length of text consumed by the tag
	* @return Tag
	*/
	public function addStartTag($name, $pos, $len)
	{
		return $this->addTag(Tag::START_TAG, $name, $pos, $len);
	}

	/**
	* Add an end tag
	*
	* @param  string  $name Name of the tag
	* @param  integer $pos  Position of the tag in the text
	* @param  integer $len  Length of text consumed by the tag
	* @return Tag
	*/
	public function addEndTag($name, $pos, $len)
	{
		return $this->addTag(Tag::END_TAG, $name, $pos, $len);
	}

	/**
	* Add a self-closing tag
	*
	* @param  string  $name Name of the tag
	* @param  integer $pos  Position of the tag in the text
	* @param  integer $len  Length of text consumed by the tag
	* @return Tag
	*/
	public function addSelfClosingTag($name, $pos, $len)
	{
		return $this->addTag(Tag::SELF_CLOSING_TAG, $name, $pos, $len);
	}

	/**
	* Add a 0-width "br" tag to force a line break at given position
	*
	* @param  integer $pos  Position of the tag in the text
	* @return Tag
	*/
	public function addBrTag($pos)
	{
		return $this->addTag(Tag::SELF_CLOSING_TAG, 'br', $pos, 0);
	}

	/**
	* Add an "ignore" tag
	*
	* @param  integer $pos  Position of the tag in the text
	* @param  integer $len  Length of text consumed by the tag
	* @return Tag
	*/
	public function addIgnoreTag($pos, $len)
	{
		return $this->addTag(Tag::SELF_CLOSING_TAG, 'i', $pos, $len);
	}

	/**
	* Add a paragraph break at given position
	*
	* Uses a zero-width tag that is actually never output in the result
	*
	* @param  integer $pos  Position of the tag in the text
	* @return Tag
	*/
	public function addParagraphBreak($pos)
	{
		return $this->addTag(Tag::SELF_CLOSING_TAG, 'pb', $pos, 0);
	}

	/**
	* Add a copy of given tag at given position and length
	*
	* @param  Tag     $tag Original tag
	* @param  integer $pos Copy's position
	* @param  integer $len Copy's length
	* @return Tag          Copy tag
	*/
	public function addCopyTag(Tag $tag, $pos, $len)
	{
		$copy = $this->addTag($tag->getType(), $tag->getName(), $pos, $len);
		$copy->setAttributes($tag->getAttributes());
		$copy->setSortPriority($tag->getSortPriority());

		return $copy;
	}

	/**
	* Add a tag
	*
	* @param  integer $type Tag's type
	* @param  string  $name Name of the tag
	* @param  integer $pos  Position of the tag in the text
	* @param  integer $len  Length of text consumed by the tag
	* @return Tag
	*/
	protected function addTag($type, $name, $pos, $len)
	{
		// Create the tag
		$tag = new Tag($type, $name, $pos, $len);

		// Set this tag's rules bitfield
		if (isset($this->tagsConfig[$name]))
		{
			$tag->setFlags($this->tagsConfig[$name]['rules']['flags']);
		}

		// Invalidate this tag if it's an unknown tag, a disabled tag, if either of its length or
		// position is negative or if it's out of bounds
		if (!isset($this->tagsConfig[$name]) && !$tag->isSystemTag())
		{
			$tag->invalidate();
		}
		elseif (!empty($this->tagsConfig[$name]['isDisabled']))
		{
			$this->logger->warn(
				'Tag is disabled',
				[
					'tag'     => $tag,
					'tagName' => $name
				]
			);
			$tag->invalidate();
		}
		elseif ($len < 0 || $pos < 0 || $pos + $len > $this->textLen)
		{
			$tag->invalidate();
		}
		else
		{
			// If the stack is sorted we check whether this tag should be stored at a lower offset
			// than the last tag which would mean we need to sort the stack. Note that we cannot use
			// compareTags() to break ties here because setSortPriority() can be called *after* tags
			// have been put on the stack, therefore we need to properly sort the stack if the
			// positions are the same
			if ($this->tagStackIsSorted
			 && !empty($this->tagStack)
			 && $tag->getPos() >= end($this->tagStack)->getPos())
			{
				$this->tagStackIsSorted = false;
			}

			$this->tagStack[] = $tag;
		}

		return $tag;
	}

	/**
	* Add a pair of tags
	*
	* @param  string  $name     Name of the tags
	* @param  integer $startPos Position of the start tag
	* @param  integer $startLen Length of the starttag
	* @param  integer $endPos   Position of the start tag
	* @param  integer $endLen   Length of the starttag
	* @return Tag               Start tag
	*/
	public function addTagPair($name, $startPos, $startLen, $endPos, $endLen)
	{
		$tag = $this->addStartTag($name, $startPos, $startLen);
		$tag->pairWith($this->addEndTag($name, $endPos, $endLen));

		return $tag;
	}

	/**
	* Sort tags by position and precedence
	*
	* @return void
	*/
	protected function sortTags()
	{
		usort($this->tagStack, __CLASS__ . '::compareTags');
		$this->tagStackIsSorted = true;
	}

	/**
	* sortTags() callback
	*
	* Tags are stored as a stack, in LIFO order. We sort tags by position _descending_ so that they
	* are processed in the order they appear in the text.
	*
	* @param  Tag     $a First tag to compare
	* @param  Tag     $b Second tag to compare
	* @return integer
	*/
	static protected function compareTags(Tag $a, Tag $b)
	{
		$aPos = $a->getPos();
		$bPos = $b->getPos();

		// First we order by pos descending
		if ($aPos !== $bPos)
		{
			return $bPos - $aPos;
		}

		// If the tags start at the same position, we'll use their sortPriority if applicable. Tags
		// with a lower value get sorted last, which means they'll be processed first. IOW, -10 is
		// processed before 10
		if ($a->getSortPriority() !== $b->getSortPriority())
		{
			return $b->getSortPriority() - $a->getSortPriority();
		}

		// If the tags start at the same position and have the same priority, we'll sort them
		// according to their length, with special considerations for  zero-width tags
		$aLen = $a->getLen();
		$bLen = $b->getLen();

		if (!$aLen || !$bLen)
		{
			// Zero-width end tags are ordered after zero-width start tags so that a pair that ends
			// with a zero-width tag has the opportunity to be closed before another pair starts
			// with a zero-width tag. For example, the pairs that would enclose each of the letters
			// in the string "XY". Self-closing tags are ordered between end tags and start tags in
			// an attempt to keep them out of tag pairs
			if (!$aLen && !$bLen)
			{
				$order = [
					Tag::END_TAG          => 0,
					Tag::SELF_CLOSING_TAG => 1,
					Tag::START_TAG        => 2
				];

				return $order[$b->getType()] - $order[$a->getType()];
			}

			// Here, we know that only one of $a or $b is a zero-width tags. Zero-width tags are
			// ordered after wider tags so that they have a chance to be processed before the next
			// character is consumed, which would force them to be skipped
			return ($aLen) ? -1 : 1;
		}

		// Here we know that both tags start at the same position and have a length greater than 0.
		// We sort tags by length ascending, so that the longest matches are processed first. If
		// their length is identical, the order is undefined as PHP's sort isn't stable
		return $aLen - $bLen;
	}
}