<?php

use \Everzet\HTAML\Parser;

/*
 * This file is part of the HTAML package.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class ParserTest extends \PHPUnit_Framework_TestCase
{
    protected function parse($value)
    {
        $parser = new Parser($value);
        return $parser->parse();
    }

    public function testDoctypes()
    {
        $this->assertEquals('<?xml version="1.0" encoding="utf-8" ?>', $this->parse('!!! xml'));
        $this->assertEquals('<!DOCTYPE html>', $this->parse('!!! 5'));
    }

    public function testUnknownFilter()
    {
        $this->setExpectedException("Everzet\\HTAML\\ParserException");
        $this->parse(":doesNotExist\n");
    }

    public function testLineEndings()
    {
        $tags = array('p', 'div', 'img');
        $html = implode("\n", array('<p></p>', '<div></div>', '<img />'));

        $this->assertEquals($html, $this->parse(implode("\r\n", $tags)));
        $this->assertEquals($html, $this->parse(implode("\r", $tags)));
        $this->assertEquals($html, $this->parse(implode("\n", $tags)));
    }

    public function testSingleQuotes()
    {
        $this->assertEquals("<p>'foo'</p>", $this->parse("p 'foo'"));
        $this->assertEquals("<p>\n  'foo'\n</p>", $this->parse("p\n  | 'foo'"));
        $this->assertEquals(<<<HTML
<?php \$path = 'foo' ?>
<a href="/<?php echo \$path ?>"></a>
HTML
, $this->parse(<<<HTAML
- \$path = 'foo'
a(href='/{{\$path}}')
HTAML
));
    }

    public function testTags()
    {
        $str = implode("\n", array('p', 'div', 'img'));
        $html = implode("\n", array('<p></p>', '<div></div>', '<img />'));

        $this->assertEquals($html, $this->parse($str), 'Test basic tags');
        $this->assertEquals('<div class="something"></div>', $this->parse('div.something'),
            'Test classes');
        $this->assertEquals('<div id="something"></div>', $this->parse('div#something'),
            'Test ids');
        $this->assertEquals('<div class="something"></div>', $this->parse('.something'),
            'Test stand-alone classes');
        $this->assertEquals('<div id="something"></div>', $this->parse('#something'),
            'Test stand-alone ids');
        $this->assertEquals('<div id="foo" class="bar"></div>', $this->parse('#foo.bar'));
        $this->assertEquals('<div id="foo" class="bar"></div>', $this->parse('.bar#foo'));
        $this->assertEquals('<div id="foo" class="bar"></div>',
            $this->parse('div#foo(class="bar")'));
        $this->assertEquals('<div id="foo" class="bar"></div>', 
            $this->parse('div(class="bar")#foo'));
        $this->assertEquals('<div id="bar" class="foo"></div>', 
            $this->parse('div(id="bar").foo'));
        $this->assertEquals('<div class="foo bar baz"></div>', $this->parse('div.foo.bar.baz'));
        $this->assertEquals('<div class="bar baz foo"></div>',
            $this->parse('div(class="foo").bar.baz'));
        $this->assertEquals('<div class="foo baz bar"></div>',
            $this->parse('div.foo(class="bar").baz'));
        $this->assertEquals('<div class="foo bar baz"></div>',
            $this->parse('div.foo.bar(class="baz")'));
        $this->assertEquals('<div class="a-b2"></div>',
            $this->parse('div.a-b2'));
        $this->assertEquals('<div class="a_b2"></div>',
            $this->parse('div.a_b2'));
        $this->assertEquals('<fb:user></fb:user>',
            $this->parse('fb:user'));
    }

    public function testNestedTags()
    {
        $htaml = <<<HTAML
ul
  li a
  li b
  li
    ul
      li c
      li d
  li e
HTAML;
        $html = <<<HTML
<ul>
  <li>a</li>
  <li>b</li>
  <li>
    <ul>
      <li>c</li>
      <li>d</li>
    </ul>
  </li>
  <li>e</li>
</ul>
HTML;
        $this->assertEquals($html, $this->parse($htaml));

        $htaml = <<<HTAML
a(href="#") foo
  | bar
  | baz
HTAML;
        $html = <<<HTML
<a href="#">
  foo
  bar
  baz
</a>
HTML;
        $this->assertEquals($html, $this->parse($htaml));

        $htaml = <<<HTAML
ul  
  li  one
  ul two
    li three
HTAML;
        $html = <<<HTML
<ul>
  <li>one</li>
  <ul>
    two
    <li>three</li>
  </ul>
</ul>
HTML;
        $this->assertEquals($html, $this->parse($htaml));
    }

    public function testVariableLengthNewlines()
    {
        $htaml = <<<HTAML
ul
  li a
  
  li b
 
         
  li
    ul
      li c

      li d
  li e
HTAML;
        $html = <<<HTML
<ul>
  <li>a</li>
  <li>b</li>
  <li>
    <ul>
      <li>c</li>
      <li>d</li>
    </ul>
  </li>
  <li>e</li>
</ul>
HTML;
        $this->assertEquals($html, $this->parse($htaml));
    }

    public function testNewlines()
    {
        $htaml = <<<HTAML
ul
  li a
  
  
  
  
  li b
  li
    
    ul
      
      li c
      li d
  li e
HTAML;
        $html = <<<HTML
<ul>
  <li>a</li>
  <li>b</li>
  <li>
    <ul>
      <li>c</li>
      <li>d</li>
    </ul>
  </li>
  <li>e</li>
</ul>
HTML;
        //$this->assertEquals($html, $this->parse($htaml));
    }

    public function testTagText()
    {
        $this->assertEquals('some random text', $this->parse('| some random text'));
        $this->assertEquals('<p>some random text</p>', $this->parse('p some random text'));
    }

    public function testTagTextBlock()
    {
        $this->assertEquals("<p>\n  foo\n  bar\n  baz\n</p>", $this->parse("p\n  | foo\n  | bar\n  | baz"));
        $this->assertEquals("<label>\n  Password:\n  <input />\n</label>", $this->parse("label\n  | Password:\n  input"));
    }

    public function testTagTextCodeInsertion()
    {
        $this->assertEquals('yo, <?php echo $htaml ?> is cool', $this->parse('| yo, {{$htaml}} is cool'));
        $this->assertEquals('<p>yo, <?php echo $htaml ?> is cool</p>', $this->parse('p yo, {{$htaml}} is cool'));
        $this->assertEquals('<p>yo, <?php echo $htaml || $jade ?> is cool</p>', $this->parse('p yo, {{$htaml || $jade}} is cool'));
        $this->assertEquals('yo, <?php echo $htaml || $jade ?> is cool', $this->parse('| yo, {{$htaml || $jade}} is cool'));
    }

    public function testHtml5Mode()
    {
        $this->assertEquals("<!DOCTYPE html>\n<input type=\"checkbox\" checked>", $this->parse("!!! 5\ninput(type=\"checkbox\", checked)"));
        $this->assertEquals("<!DOCTYPE html>\n<input type=\"checkbox\" checked>", $this->parse("!!! 5\ninput(type=\"checkbox\", checked: true)"));
        $this->assertEquals("<!DOCTYPE html>\n<input type=\"checkbox\">", $this->parse("!!! 5\ninput(type=\"checkbox\", checked: false)"));
    }

    public function testAttrs()
    {
        $this->assertEquals('<img src="&lt;script&gt;" />', $this->parse('img(src="<script>")'), 'Test attr escaping');
        $this->assertEquals('<a data-attr="bar"></a>', $this->parse('a(data-attr:"bar")'));
        $this->assertEquals('<a data-attr="bar" data-attr-2="baz"></a>', $this->parse('a(data-attr:"bar", data-attr-2:"baz")'));
        $this->assertEquals('<a title="foo,bar"></a>', $this->parse('a(title: "foo,bar")'));
        $this->assertEquals('<a title="foo,bar"></a>', $this->parse('a(title: "foo,bar" )'));
        $this->assertEquals('<a title="foo,bar" href="#"></a>', $this->parse('a(title: "foo,bar", href="#")'));

        $this->assertEquals('<p class="foo"></p>', $this->parse("p(class='foo')"), 'Test single quoted attrs');
        $this->assertEquals('<input type="checkbox" checked="checked" />', $this->parse('input(type="checkbox", checked)'));
        $this->assertEquals('<input type="checkbox" checked="checked" />', $this->parse('input(type="checkbox", checked: true)'));
        $this->assertEquals('<input type="checkbox" />', $this->parse('input(type="checkbox", checked: false)'));
        $this->assertEquals('<input type="checkbox" />', $this->parse('input(type="checkbox", checked: null)'));
        $this->assertEquals('<input type="checkbox" />', $this->parse('input(type="checkbox", checked: "")'));

        $this->assertEquals('<img src="/foo.png" />', $this->parse('img(src="/foo.png")'), 'Test attr =');
        $this->assertEquals('<img src="/foo.png" />', $this->parse('img(src  =  "/foo.png")'), 'Test attr = whitespace');
        $this->assertEquals('<img src="/foo.png" />', $this->parse('img(src:"/foo.png")'), 'Test attr :');
        $this->assertEquals('<img src="/foo.png" />', $this->parse('img(src  :  "/foo.png")'), 'Test attr : whitespace');

        $this->assertEquals('<img src="/foo.png" alt="just some foo" />',
            $this->parse('img(src: "/foo.png", alt: "just some foo")'));
        $this->assertEquals('<img src="/foo.png" alt="just some foo" />',
            $this->parse('img(src   : "/foo.png", alt  :  "just some foo")'));
        $this->assertEquals('<img src="/foo.png" alt="just some foo" />',
            $this->parse('img(src="/foo.png", alt="just some foo")'));
        $this->assertEquals('<img src="/foo.png" alt="just some foo" />',
            $this->parse('img(src = "/foo.png", alt = "just some foo")'));

        $this->assertEquals('<p class="foo,bar,baz"></p>', $this->parse('p(class="foo,bar,baz")'));
        $this->assertEquals('<a href="http://google.com" title="Some : weird = title"></a>',
            $this->parse('a(href: "http://google.com", title: "Some : weird = title")'));
        $this->assertEquals('<label for="name"></label>',
            $this->parse('label(for="name")'));
        $this->assertEquals('<meta name="viewport" content="width=device-width" />',
            $this->parse("meta(name: 'viewport', content: 'width=device-width')"), 'Attrs with separators');
        $this->assertEquals('<meta name="viewport" content="width=device-width" />',
            $this->parse("meta(name: 'viewport', content='width=device-width')"), 'Attrs with separators');
        $this->assertEquals('<div style="color: white"></div>',
            $this->parse("div(style='color: white')"));
        $this->assertEquals('<p class="foo"></p>',
            $this->parse("p('class'='foo')"), 'Keys with single quotes');
        $this->assertEquals('<p class="foo"></p>',
            $this->parse("p(\"class\": 'foo')"), 'Keys with double quotes');
    }

    public function testCodeAttrs()
    {
        $this->assertEquals('<p id="<?php echo $name ?>"></p>', $this->parse('p(id: {{$name}})'));
        $this->assertEquals('<p foo="<?php echo $name || "<default />" ?>"></p>', $this->parse('p(foo: {{$name || "<default />"}})'));
    }

    public function testCode()
    {
        $htaml = <<<HTAML
- \$foo = "<script>";
= \$foo
HTAML;
        $html = <<<HTML
<?php \$foo = "<script>"; ?>
<?php echo \$foo ?>
HTML;
        $this->assertEquals($html, $this->parse($htaml));

        $htaml = <<<HTAML
- \$foo = "<script>";
- if (null !== \$foo)
  = \$foo
HTAML;
        $html = <<<HTML
<?php \$foo = "<script>"; ?>
<?php if (null !== \$foo): ?>
  <?php echo \$foo ?>
<?php endif; ?>
HTML;
        $this->assertEquals($html, $this->parse($htaml));

        $htaml = <<<HTAML
- \$foo = "<script>";
p
  - if (null !== \$foo)
    = \$foo
HTAML;
        $html = <<<HTML
<?php \$foo = "<script>"; ?>
<p>
  <?php if (null !== \$foo): ?>
    <?php echo \$foo ?>
  <?php endif; ?>
</p>
HTML;
        $this->assertEquals($html, $this->parse($htaml));

        $htaml = <<<HTAML
- \$foo = "<script>";
p
  - if (null !== \$foo)
    strong= \$foo
HTAML;
        $html = <<<HTML
<?php \$foo = "<script>"; ?>
<p>
  <?php if (null !== \$foo): ?>
    <strong><?php echo \$foo ?></strong>
  <?php endif; ?>
</p>
HTML;
        $this->assertEquals($html, $this->parse($htaml));

        $htaml = <<<HTAML
- \$foo = "<script>";
p
  - if (null !== \$foo)
    strong= \$foo
  - else
    h2= \$foo / 2
HTAML;
        $html = <<<HTML
<?php \$foo = "<script>"; ?>
<p>
  <?php if (null !== \$foo): ?>
    <strong><?php echo \$foo ?></strong>
  <?php else: ?>
    <h2><?php echo \$foo / 2 ?></h2>
  <?php endif; ?>
</p>
HTML;
        $this->assertEquals($html, $this->parse($htaml));

        $htaml = <<<HTAML
- \$foo = "<script>";
p
  - if (null !== \$foo)
    strong= \$foo
  - else
    h2= \$foo / 2
HTAML;
        $html = <<<HTML
<?php \$foo = "<script>"; ?>
<p>
  <?php if (null !== \$foo): ?>
    <strong><?php echo \$foo ?></strong>
  <?php else: ?>
    <h2><?php echo \$foo / 2 ?></h2>
  <?php endif; ?>
</p>
HTML;
        $this->assertEquals($html, $this->parse($htaml));

        $htaml = <<<HTAML
- \$foo = "<script>";
p
  - switch (\$foo)
    -case 2
      p.foo= \$foo
    - case 'strong'
      strong#name= \$foo * 2
    -   case 5
      p some text
HTAML;
        $html = <<<HTML
<?php \$foo = "<script>"; ?>
<p>
  <?php switch (\$foo): ?>
    <?php case 2: ?>
      <p class="foo"><?php echo \$foo ?></p>
    <?php break; ?>
    <?php case 'strong': ?>
      <strong id="name"><?php echo \$foo * 2 ?></strong>
    <?php break; ?>
    <?php case 5: ?>
      <p>some text</p>
    <?php break; ?>
  <?php endswitch; ?>
</p>
HTML;
        $this->assertEquals($html, $this->parse($htaml));
    }
}
