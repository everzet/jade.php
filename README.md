# Jade - template compiler for PHP5.3

*Jade* is a high performance template compiler heavily influenced by [Haml](http://haml-lang.com)
and implemented for PHP 5.3.

## Features

  - high performance parser
  - great readability
  - contextual error reporting at compile &amp; run time
  - html 5 mode (using the _!!! 5_ doctype)
  - combine dynamic and static tag classes
  - no tag prefix
  - clear & beautiful HTML output
  - filters
    - :php
    - :cdata
    - :css
    - :javascript
  - you even can write & add own filters throught API
  - [TextMate Bundle](http://github.com/miksago/jade-tmbundle)
  - [VIM Plugin](http://github.com/vim-scripts/jade.vim.git)

## Public API

    $dumper = new PHPDumper();
    $dumper->registerVisitor('tag', new AutotagsVisitor());
    $dumper->registerFilter('javascript', new JavaScriptFilter());
    $dumper->registerFilter('cdata', new CDATAFilter());
    $dumper->registerFilter('php', new PHPFilter());
    $dumper->registerFilter('style', new CSSFilter());
    
    // Initialize parser & Jade
    $parser = new Parser(new Lexer());
    $jade   = new Jade($parser, $dumper);
	
	// Parse a template (both string & file containers)
    echo $jade->render($template);

## Syntax

### Line Endings

**CRLF** and **CR** are converted to **LF** before parsing.

### Indentation

Jade is indentation based, however currently only supports a _2 space_ indent.

### Tags

A tag is simply a leading word:

	html

for example is converted to `<html></html>`

tags can also have ids:

	div#container

which would render `<div id="container"></div>`

how about some classes?

	div.user-details

renders `<div class="user-details"></div>`

multiple classes? _and_ an id? sure:

	div#foo.bar.baz

renders `<div id="foo" class="bar baz"></div>`

div div div sure is annoying, how about:

	#foo
	.bar

which is syntactic sugar for what we have already been doing, and outputs:

	<div id="foo"></div><div class="bar"></div>

jade.php has a feature, called "autotags". It's just snippets for tags. Autotags will expand to basic tags with custom attributes. For example:

	input:text

will expand to `<input type="text" />` & it's the same as `input( type="text" )`, but shorter.
Another examples:

	input:submit( value="Send" )

will become `<input type="submit" value="Send" />`.

You can even add you own autotags with:

	$parser->setAutotag('input:progress', 'input', array('type'=>'text', class=>'progress-bar'));

that will expands to `<input type="text" class="progress-bar" />`.

It also supports new HTML5 tags (`input:email` => `<input type="email"/>`).

### Tag Text

Simply place some content after the tag:

	p wahoo!

renders `<p>wahoo!</p>`.

well cool, but how about large bodies of text:

	p
	  | foo bar baz
	  | rawr rawr
	  | super cool
	  | go Jade go

renders `<p>foo bar baz rawr.....</p>`

Actually want `<?php echo ... ?>` for some reason? Use `{{}}` instead:

	p {{$something}}

now we have `<p><?php echo $something ?></p>`

### Nesting

	ul
	  li one
	  li two
	  li three

### Attributes

Jade currently supports '(' and ')' as attribute delimiters.

	a(href='/login', title='View login page') Login

Alternatively we may use the colon to separate pairs:

	a(href: '/login', title: 'View login page') Login

Boolean attributes are also supported:

	input(type="checkbox", checked)

Boolean attributes with code will only output the attribute when `true`:

	input(type="checkbox", checked: someValue)

Note: Leading / trailing whitespace is _ignore_ for attr pairs.

### Doctypes

To add a doctype simply use `!!!` followed by an optional value:

	!!!

Will output the _transitional_ doctype, however:

	!!! 5

Will output html 5's doctype. Below are the doctypes
defined by default, which can easily be extended:

	$doctypes = array(
	       '5' => '<!DOCTYPE html>',
	       'xml' => '<?xml version="1.0" encoding="utf-8" ?>',
	       'default' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
	       'transitional' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
	       'strict' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
	       'frameset' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
	       '1.1' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
	       'basic' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">',
	       'mobile' => '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.2//EN" "http://www.openmobilealliance.org/tech/DTD/xhtml-mobile12.dtd">'
	   );

## Comments

### Jade Comments

Jade supports sharp comments (`//- COMMENT`). So jade block:

	//- JADE
	- $foo = "<script>";
	p
	//- ##### COMMENTS ARE SUPPER! ######
	  - switch ($foo)
	    -case 2
	      p.foo= $foo
	//-    - case 'strong'
	  //-      strong#name= $foo * 2
	    -   case 5
	      p some text

will be compiled into:

	<?php $foo = "<script>"; ?>
	<p>
	  <?php switch ($foo) ?>
	    <?php case 2 ?>
	      <p class="foo"><?php echo $foo ?></p>
	    <?php break; ?>
	    <?php case 5 ?>
	      <p>some text</p>
	    <?php break; ?>
	  <?php endswitch; ?>
	</p>

### HTML Comments

Jade supports HTML comments (`// comment`). So block:

	peanutbutterjelly
	  // This is the peanutbutterjelly element
	  | I like sandwiches!

will become:

	<peanutbutterjelly>
	  <!-- This is the peanutbutterjelly element -->
	  I like sandwiches!
	</peanutbutterjelly>

As with multiline comments:

	//
	  p This doesn't render...
	  div
	    h1 Because it's commented out!

that compile to:

	<!--
	  <p>This doesn't render...</p>
	  <div>
	    <h1>Because it's commented out!</h1>
	  </div>
	-->

### IE Conditional Comments

Also, Jade supports IE conditional comments, so:

	// [if IE]
	  a( href = 'http://www.mozilla.com/en-US/firefox/' )
	    h1 Get Firefox

will be parsed to:

	<!--[if IE]>
	  <a href="http://www.mozilla.com/en-US/firefox/">
	    <h1>Get Firefox</h1>
	  </a>
	<![endif]-->

## Filters

Filters are prefixed with `:`, for example `:javascript` or `:cdata` and
pass the following block of text to an arbitrary function for processing. View the _features_
at the top of this document for available filters.

	body
	  :php
	    | $data = 40;
	    | $data /= 2;
	    | echo $data;

Renders:

	<body>
	  <?php
	    $data = 40;
	    $data /= 2;
	    echo $data;
	  ?>
	</body>

## Code

### Buffered / Non-buffered output

Jade currently supports two classifications of executable code. The first
is prefixed by `-`, and is not buffered:

	- var $foo = 'bar';

This can be used for conditionals, or iteration:

	- foreach ($items as $item):
	  p= $item

Due to Jade's buffering techniques the following is valid as well:

	- if ($foo):
	  ul
	    li yay
	    li foo
	    li worked
	- else:
	  p hey! didnt work

Second is echoed code, which is used to
echo a return value, which is prefixed by `=`:

	- $foo = 'bar'
	= $foo
	h1= $foo

Which outputs

	<?php $foo = 'bar' ?>
	<?php echo $foo ?>
	<h1><?php echo $foo ?></h1>

### Code blocks

Also, Jade has Code Blocks, that supports basic PHP template syntax:

	ul
	  - while (true):
	    li item

Will be rendered to:

	<ul>
	  <?php while (true): ?>
	    <li>item</li>
	  <?php endwhile; ?>
	</ul>

But don't forget about colons `:` after instructions start (`- if(true) :`).

There's bunch of default ones: `if`, `else`, `elseif`, `while`, `for`, `foreach`, `switch`, `case`.
