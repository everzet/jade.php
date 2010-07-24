<?php

use \Everzet\HTAML\Parser;

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
        $this->assertEquals($html, $this->parse($htaml));
    }
}
