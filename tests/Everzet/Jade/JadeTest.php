<?php

use Everzet\Jade\Jade;
use Everzet\Jade\Parser;
use Everzet\Jade\Lexer\Lexer;
use Everzet\Jade\Dumper\PHPDumper;
use Everzet\Jade\Visitor\AutotagsVisitor;

/*
 * This file is part of the Jade.php.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Parser/Dumper test. 
 */
class JadeTest extends \PHPUnit_Framework_TestCase
{
    protected $jade;

    public function __construct()
    {
        $parser = new Parser(new Lexer());
        $dumper = new PHPDumper();
        $dumper->registerVisitor('tag', new AutotagsVisitor());

        $this->jade = new Jade($parser, $dumper);
    }

    protected function parse($value)
    {
        return $this->jade->render($value);
    }

    public function testDoctypes()
    {
        $this->assertEquals('<?xml version="1.0" encoding="utf-8" ?>' , $this->parse('!!! xml'));
        $this->assertEquals('<!DOCTYPE html>' , $this->parse('!!! 5'));
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
, $this->parse(<<<Jade
- \$path = 'foo'
a(href='/{{\$path}}')
Jade
));
    }

    public function testTags()
    {
        $str = implode("\n", array('p', 'div', 'img'));
        $html = implode("\n", array('<p></p>', '<div></div>', '<img />'));

        $this->assertEquals($html, $this->parse($str), 'Test basic tags');
        $this->assertEquals('<div id="item" class="something"></div>', 
            $this->parse('#item.something'), 'Test classes');
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
        $this->assertEquals('<div class="foo bar baz"></div>',
            $this->parse('div(class="foo").bar.baz'));
        $this->assertEquals('<div class="foo bar baz"></div>',
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
        $jade = <<<Jade
ul
  li a
  li b
  li
    ul
      li c
      li d
  li e
Jade;
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
        $this->assertEquals($html, $this->parse($jade));

        $jade = <<<Jade
a(href="#") foo
  | bar
  | baz
Jade;
        $html = <<<HTML
<a href="#">
  foo
  bar
  baz
</a>
HTML;
        $this->assertEquals($html, $this->parse($jade));

        $jade = <<<Jade
ul      
  li  one
  ul two
    li three
Jade;
        $html = <<<HTML
<ul>
  <li>one</li>
  <ul>
    two
    <li>three</li>
  </ul>
</ul>
HTML;
        $this->assertEquals($html, $this->parse($jade));

        $jade = <<<Jade
div#loading
  div.loading-indicator
Jade;
        $html = <<<HTML
<div id="loading">
  <div class="loading-indicator"></div>
</div>
HTML;
        $this->assertEquals($html, $this->parse($jade));
    }

    public function testVariableLengthNewlines()
    {
        $jade = <<<Jade
ul
  li a
  
  li b
 
         
  li
    ul
      li c

      li d
  li e
Jade;
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
        $this->assertEquals($html, $this->parse($jade));
    }
    
    public function testNewlines()
    {
        $jade = <<<Jade
ul
  li a
  
    
      
    
  li b
  li
      

    ul
            
      li c
      li d
              
  li e
Jade;
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
        $this->assertEquals($html, $this->parse($jade));

        $jade = <<<Jade
ul
  li visit
    a(href="/foo") foo
Jade;
        $html = <<<HTML
<ul>
  <li>
    visit
    <a href="/foo">foo</a>
  </li>
</ul>
HTML;
        $this->assertEquals($html, $this->parse($jade));
    }
    
    public function testTagText()
    {
        $this->assertEquals('some random text', $this->parse('| some random text'));
        $this->assertEquals('<p>some random text</p>', $this->parse('p some random text'));
    }

    public function testTagTextBlock()
    {
        $this->assertEquals("<p>\n  foo\n     bar\n   baz\n</p>", $this->parse("p\n  | foo\n  |    bar\n  |  baz"));
        $this->assertEquals("<label>\n  Password:\n  <input />\n</label>", $this->parse("label\n  | Password:\n  input"));
    }

    public function testTagTextCodeInsertion()
    {
        $this->assertEquals('yo, <?php echo $jade ?> is cool', $this->parse('| yo, <?php echo $jade ?> is cool'));
        $this->assertEquals('<p>yo, <?php echo $jade ?> is cool</p>', $this->parse('p yo, <?php echo $jade ?> is cool'));
        $this->assertEquals('<p>yo, <?php echo $jade || $jade ?> is cool</p>', $this->parse('p yo, <?php echo $jade || $jade ?> is cool'));
        $this->assertEquals('yo, <?php echo $jade || $jade ?> is cool', $this->parse('| yo, <?php echo $jade || $jade ?> is cool'));
    }

    public function testHtml5Mode()
    {
        $this->assertEquals("<!DOCTYPE html>\n<input type=\"checkbox\" checked=\"checked\" />", $this->parse("!!! 5\ninput(type=\"checkbox\", checked)"));
        $this->assertEquals("<!DOCTYPE html>\n<input type=\"checkbox\" checked=\"checked\" />", $this->parse("!!! 5\ninput(type=\"checkbox\", checked: true)"));
        $this->assertEquals("<!DOCTYPE html>\n<input type=\"checkbox\" />", $this->parse("!!! 5\ninput(type=\"checkbox\", checked: false)"));
    }
    
    public function testAttrs()
    {
        $this->assertEquals('<img src="&lt;script&gt;" />', $this->parse('img(src="<script>")'), 'Test attr escaping');
        $this->assertEquals('<a data-attr="bar"></a>', $this->parse('a(data-attr:"bar")'));
        $this->assertEquals('<a data-attr="bar" data-attr-2="baz"></a>', $this->parse('a(data-attr:"bar", data-attr-2:"baz")'));
        $this->assertEquals('<a title="foo,bar"></a>', $this->parse('a(title: "foo,bar")'));
        $this->assertEquals('<a title="foo , bar"></a>', $this->parse('a(title: "foo , bar" )'));
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

//        $this->assertEquals(
//          '<meta name="viewport" content="width=device-width, user-scalable=no" />',
//          $this->parse(
//            "meta(name: 'viewport', content:\"width=device-width, user-scalable=no\")"
//          ), 'Commas in attrs'
//        );
//        $this->assertEquals(
//          '<meta name="viewport" content="width=device-width, user-scalable=no" />',
//          $this->parse(
//            "meta(name: 'viewport', content:'width=device-width, user-scalable=no')"
//          ), 'Commas in attrs'
//        );
    }
    
    public function testCodeAttrs()
    {
        $this->assertEquals('<p id="<?php echo $name ?>"></p>', $this->parse('p(id: {{$name}})'));
        $this->assertEquals('<p id="<?php echo \'name \' . $name . " =)" ?>"></p>', $this->parse('p(id: {{\'name \' . $name . " =)"}})'));
        $this->assertEquals('<p foo="<?php echo $name || "<default />" ?>"></p>', $this->parse('p(foo: {{$name || "<default />"}})'));
        $this->assertEquals('<p id="<?php echo \'name \' . $name . " =)" ?>">Hello, (bracket =) )</p>', $this->parse('p(id: {{\'name \' . $name . " =)"}}) Hello, (bracket =) )'));
    }
    
    public function testCode()
    {
        $jade = <<<Jade
- \$foo = "<script>";
= \$foo
Jade;
        $html = <<<HTML
<?php \$foo = "<script>"; ?>
<?php echo \$foo ?>
HTML;
        $this->assertEquals($html, $this->parse($jade));

        $jade = <<<Jade

input
input:hidden( name="sf_method", value="PUT" )

Jade;
        $html = <<<HTML
<input />
<input name="sf_method" value="PUT" type="hidden" />
HTML;
        $this->assertEquals($html, $this->parse($jade));

        $jade = <<<Jade

input
= \$form->renderHiddenFields()

Jade;
        $html = <<<HTML
<input />
<?php echo \$form->renderHiddenFields() ?>
HTML;
        $this->assertEquals($html, $this->parse($jade));

        $jade = <<<Jade
- foreach(\$contacts as \$contact):
  - include_partial('contactForm', array('contact' => \$contact))
Jade;
        $html = <<<HTML
<?php foreach(\$contacts as \$contact): ?>
  <?php include_partial('contactForm', array('contact' => \$contact)) ?>
<?php endforeach; ?>
HTML;
        $this->assertEquals($html, $this->parse($jade));

        $jade = <<<Jade
- for(\$i = 0; \$i < count(\$contacts); \$i++):
  - include_partial('contactForm', array('contact' => \$contacts[\$i]))
Jade;
        $html = <<<HTML
<?php for(\$i = 0; \$i < count(\$contacts); \$i++): ?>
  <?php include_partial('contactForm', array('contact' => \$contacts[\$i])) ?>
<?php endfor; ?>
HTML;
        $this->assertEquals($html, $this->parse($jade));

        $jade = <<<Jade

input:hidden( name="sf_method", value="PUT" )
= \$form->renderHiddenFields()

Jade;
        $html = <<<HTML
<input name="sf_method" value="PUT" type="hidden" />
<?php echo \$form->renderHiddenFields() ?>
HTML;
        $this->assertEquals($html, $this->parse($jade));

        $jade = <<<Jade
- \$foo = "<script>";
- if ('' !== \$form['login']->renderHelp()):
  = \$foo
Jade;
        $html = <<<HTML
<?php \$foo = "<script>"; ?>
<?php if ('' !== \$form['login']->renderHelp()): ?>
  <?php echo \$foo ?>
<?php endif; ?>
HTML;
        $this->assertEquals($html, $this->parse($jade));

        $jade = <<<Jade
- if (\$sf_user->hasFlash('notice')):
  p.notice= \$sf_user->getFlash('notice')

- elseif (\$sf_user->hasFlash('error')):
  p.error= \$sf_user->getFlash('error')
Jade;
        $html = <<<HTML
<?php if (\$sf_user->hasFlash('notice')): ?>
  <p class="notice"><?php echo \$sf_user->getFlash('notice') ?></p>
<?php elseif (\$sf_user->hasFlash('error')): ?>
  <p class="error"><?php echo \$sf_user->getFlash('error') ?></p>
<?php endif; ?>
HTML;
        $this->assertEquals($html, $this->parse($jade));

        $jade = <<<Jade
- \$foo = "<script>";
p
  - if (null !== \$foo):
    = \$foo
Jade;
        $html = <<<HTML
<?php \$foo = "<script>"; ?>
<p>
  <?php if (null !== \$foo): ?>
    <?php echo \$foo ?>
  <?php endif; ?>
</p>
HTML;
        $this->assertEquals($html, $this->parse($jade));

        $jade = <<<Jade
- \$foo = "<script>";
p
  - if (null !== \$foo):
    strong= \$foo
Jade;
        $html = <<<HTML
<?php \$foo = "<script>"; ?>
<p>
  <?php if (null !== \$foo): ?>
    <strong><?php echo \$foo ?></strong>
  <?php endif; ?>
</p>
HTML;
        $this->assertEquals($html, $this->parse($jade));

        $jade = <<<Jade
- \$foo = "<script>";
p
  - if (null !== \$foo):
    strong= \$foo
  - else:
    h2= \$foo / 2
Jade;
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
        $this->assertEquals($html, $this->parse($jade));

        $jade = <<<Jade
- \$foo = "<script>";
p
  - if (null !== \$foo):
    strong= \$foo
  - else   :
    h2= \$foo / 2
Jade;
        $html = <<<HTML
<?php \$foo = "<script>"; ?>
<p>
  <?php if (null !== \$foo): ?>
    <strong><?php echo \$foo ?></strong>
  <?php else   : ?>
    <h2><?php echo \$foo / 2 ?></h2>
  <?php endif; ?>
</p>
HTML;
        $this->assertEquals($html, $this->parse($jade));

        $jade = <<<Jade
- \$foo = "<script>";
p
  - switch (\$foo) :

    -case 2 :
      p.foo= \$foo

    - case 'strong':
      strong#name= \$foo * 2

    -   case 5   :
      p some text
Jade;
        $html = <<<HTML
<?php \$foo = "<script>"; ?>
<p>
  <?php switch (\$foo) : ?>
    <?php case 2 : ?>
      <p class="foo"><?php echo \$foo ?></p>
    <?php break; ?>
    <?php case 'strong': ?>
      <strong id="name"><?php echo \$foo * 2 ?></strong>
    <?php break; ?>
    <?php case 5   : ?>
      <p>some text</p>
    <?php break; ?>
  <?php endswitch; ?>
</p>
HTML;
        $this->assertEquals($html, $this->parse($jade));

        $jade = <<<Jade
p
  - if (5 === \$num) \$num++;
Jade;
        $html = <<<HTML
<p>
  <?php if (5 === \$num) \$num++; ?>
</p>
HTML;
        $this->assertEquals($html, $this->parse($jade));
    }

    public function testCorrectEndings()
    {
        $jade = <<<Jade
!!! strict
html
  - use_helper('LESS')
  head
    - include_http_metas()
    - include_metas()
    - include_title()
    - include_less_stylesheets()
    - include_javascripts()
  body
    a#logo( href = '#' ) logo

    ul#main-menu
      li
        a( href = '#' ) Item 1
      li
        a( href = '#' ) Второй итем
      li
        a( href = '#' ) Третий

    = \$sf_content
Jade;
        $html = <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
  <?php use_helper('LESS') ?>
  <head>
    <?php include_http_metas() ?>
    <?php include_metas() ?>
    <?php include_title() ?>
    <?php include_less_stylesheets() ?>
    <?php include_javascripts() ?>
  </head>
  <body>
    <a id="logo" href="#">logo</a>
    <ul id="main-menu">
      <li>
        <a href="#">Item 1</a>
      </li>
      <li>
        <a href="#">Второй итем</a>
      </li>
      <li>
        <a href="#">Третий</a>
      </li>
    </ul>
    <?php echo \$sf_content ?>
  </body>
</html>
HTML;

        $this->assertEquals($html, $this->parse($jade));
    }

    public function test18NStringInAttrs()
    {
        $this->assertEquals('<input type="text" value="Search" />', $this->parse('input( type="text", value="Search" )'));
        $this->assertEquals('<input type="текст" value="Поиск" />', $this->parse('input( type="текст", value="Поиск" )'));
    }
    
    public function testAutotags()
    {
        $this->assertEquals('<link media="screen" src="/css/ie6.css" rel="stylesheet" type="text/css" />',
          $this->parse('link:css( media="screen", src="/css/ie6.css" )'));
        $this->assertEquals('<input value="Search" type="text" />', $this->parse('input:text( value="Search" )'));
        $this->assertEquals('<input type="checkbox" />', $this->parse('input:checkbox'));
        $this->assertEquals('<input value="Send" type="submit" />', $this->parse('input:submit( value="Send" )'));
        $this->assertEquals('<link href="/css/screen.css" media="screen" rel="stylesheet" type="text/css" />', $this->parse('link:css(href:"/css/screen.css", media="screen")'));
        $this->assertEquals('<script charset="UTF8" src="http://stats.test.com/scripts/stat.js" type="text/javascript"></script>', $this->parse('script:js(  charset  =  "UTF8",   src:"http://stats.test.com/scripts/stat.js"  )'));
    }
    
    public function testHTMLComments()
    {
        $jade = <<<Jade
peanutbutterjelly
  // This is the peanutbutterjelly element
  | I like sandwiches!
Jade;
        $html = <<<HTML
<peanutbutterjelly>
  <!-- This is the peanutbutterjelly element -->
  I like sandwiches!
</peanutbutterjelly>
HTML;
        $this->assertEquals($html, $this->parse($jade));

        $jade = <<<Jade
//
  p This doesn't render...
  div
    h1 Because it's commented out!
Jade;
        $html = <<<HTML
<!--
  <p>This doesn't render...</p>
  <div>
    <h1>Because it's commented out!</h1>
  </div>
-->
HTML;

        $this->assertEquals($html, $this->parse($jade));
    }

    public function testHTMLConditionalComments()
    {
        $jade = <<<Jade
// [if IE]
  a( href = 'http://www.mozilla.com/en-US/firefox/' )
    h1 Get Firefox
Jade;
        $html = <<<HTML
<!--[if IE]>
  <a href="http://www.mozilla.com/en-US/firefox/">
    <h1>Get Firefox</h1>
  </a>
<![endif]-->
HTML;
        $this->assertEquals($html, $this->parse($jade));

        $jade = <<<Jade
!!! 5
html
  - use_helper('LESS')

  head
    - include_http_metas()
    - include_metas()
    - include_title()
    - include_less_stylesheets()
    - include_javascripts()

    //-
      p
        | Not printed

    // [if lt IE 9]
      script( src="http://ie7-js.googlecode.com/svn/version/2.1(beta4)/IE9.js" )
    // [if IE]
      script( src="http://html5shiv.googlecode.com/svn/trunk/html5.js" )
      script( src="http://html5shiv.googlecode.com/svn/trunk/html6.js" )
      script( src="http://html5shiv.googlecode.com/svn/trunk/html7.js" )
Jade;
        $html = <<<HTML
<!DOCTYPE html>
<html>
  <?php use_helper('LESS') ?>
  <head>
    <?php include_http_metas() ?>
    <?php include_metas() ?>
    <?php include_title() ?>
    <?php include_less_stylesheets() ?>
    <?php include_javascripts() ?>
    <!--[if lt IE 9]>
      <script src="http://ie7-js.googlecode.com/svn/version/2.1(beta4)/IE9.js"></script>
    <![endif]-->
    <!--[if IE]>
      <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
      <script src="http://html5shiv.googlecode.com/svn/trunk/html6.js"></script>
      <script src="http://html5shiv.googlecode.com/svn/trunk/html7.js"></script>
    <![endif]-->
  </head>
</html>
HTML;
        $this->assertEquals($html, $this->parse($jade));

        $jade = <<<Jade
// [if lt IE 7]
  link:css( src="/less/ie6.less" )
Jade;
        $html = <<<HTML
<!--[if lt IE 7]>
  <link src="/less/ie6.less" rel="stylesheet" type="text/css" />
<![endif]-->
HTML;
        $this->assertEquals($html, $this->parse($jade));
    }

    public function testJSLinkTag()
    {
        $this->assertEquals('<a value="hello" href="javascript:void(0)"></a>', $this->parse('a:void( value="hello" )'));
    }
}
