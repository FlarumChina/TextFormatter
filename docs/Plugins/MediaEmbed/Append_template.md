<h2>How to add a link to the original URL below the embedded content</h2>

If you want to display a link to the original page below the embedded content, the `appendTemplate()` method can be called (before adding media sites) to set a template to be displayed after the embedded content.

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->MediaEmbed->appendTemplate(
	'<a href="{@url}"><xsl:value-of select="@url"/></a>'
);
$configurator->MediaEmbed->add('youtube');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'http://www.youtube.com/watch?v=-cEzsCAzTak';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="//www.youtube.com/embed/-cEzsCAzTak"></iframe><a href="http://www.youtube.com/watch?v=-cEzsCAzTak">http://www.youtube.com/watch?v=-cEzsCAzTak</a>
```
