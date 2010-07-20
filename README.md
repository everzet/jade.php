# HTAML - template engine for PHP5.3

*HTAML* is Jade's port to PHP5.3.

*Jade* is a high performance template engine heavily influenced by [Haml](http://haml-lang.com)
and implemented with JavaScript for [node](http://nodejs.org).

## Features

  - high performance parser
  - great readability
  - contextual error reporting at compile &amp; run time
  - html 5 mode (using the _!!! 5_ doctype)
  - combine dynamic and static tag classes
  - no tag prefix
  - beautify HTML output
  - filters
    - :php
    - :cdata
    - :javascript
	- you even can write & add own filters throught API
  - [TextMate Bundle](http://github.com/miksago/jade-tmbundle)

## Public API

	$parser = new \Everzet\HTAML\Parser('string of HTAML');
	
	// Parse a string
	echo $parser->parse();

## Syntax

### Line Endings

**CRLF** and **CR** are converted to **LF** before parsing.

### Indentation

HTAML is indentation based, however currently only supports a _2 space_ indent.

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

### Tag Text

Simply place some content after the tag:

	p wahoo!

renders `<p>wahoo!</p>`.

well cool, but how about large bodies of text:

	p
	  | foo bar baz
	  | rawr rawr
	  | super cool
	  | go HTAML go

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

HTAML currently supports '(' and ')' as attribute delimiters.

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

HTAML currently supports three classifications of executable code. The first
is prefixed by `-`, and is not buffered:

	- var $foo = 'bar';

This can be used for conditionals, or iteration:

	- foreach ($items as $item)
	  p= $item

Due to HTAML's buffering techniques the following is valid as well:

	- if ($foo)
	  ul
	    li yay
	    li foo
	    li worked
	- else
	  p hey! didnt work

Next up we have buffered code, which is used to
buffer a return value, which is prefixed by `=`:

	- $foo = 'bar'
	= $foo
	h1= $foo

Which outputs `bar<h1>bar<h1/>`.

### Code blocks

Also, HTAML has Code Blocks, that supports basic PHP template syntax:

	ul
	  - while (true)
	    li item

Will be rendered to:

	<ul>
	  <?php while (true): ?>
	    <li>item</li>
	  <?php endwhile; ?>
	</ul>

There's bunch of default ones: `if`, `else`, `elseif`, `while`, `for`, `foreach`, `switch`, `case`. And you can add new with:

	$parser->setBlockEnd('slot', 'endslot');
