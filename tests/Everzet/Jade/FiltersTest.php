<?php

use \Everzet\Jade\Parser;

/*
 * This file is part of the Jade package.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class FiltersTest extends \PHPUnit_Framework_TestCase
{
    protected function parse($value)
    {
        $parser = new Parser($value);
        return $parser->parse();
    }

    public function testFilterCodeInsertion()
    {
        $this->assertEquals(
            "<script type=\"text/javascript\">\n  var name = \"<?php echo \$name ?>\";\n</script>",
            $this->parse(<<<Jade
:javascript
  | var name = "{{\$name}}";
Jade
            )
        );
    }

    public function testCDATAFilter()
    {
        $this->assertEquals(
            "<![CDATA[\n  foo\n]]>",
            $this->parse(<<<Jade
:cdata
  | foo
Jade
            )
        );
        $this->assertEquals(
            "<![CDATA[\n  foo\n  bar\n]]>",
            $this->parse(<<<Jade
:cdata
  | foo
  | bar
Jade
            )
        );
        $this->assertEquals(
            "<![CDATA[\n  foo\n  bar\n]]>\n<p>something else</p>",
            $this->parse(<<<Jade
:cdata
  | foo
  | bar
p something else
Jade
            )
        );
    }

    public function testJavaScriptFilter()
    {
        $this->assertEquals(
            "<script type=\"text/javascript\">\n  alert('foo')\n</script>",
            $this->parse(<<<Jade
:javascript
  | alert('foo')
Jade
            )
        );
    }

    public function testCSSFilter()
    {
        $this->assertEquals(
            "<style type=\"text/css\">\n  body {\n    color:#000;\n  }\n</style>",
            $this->parse(<<<Jade
:style
  | body {
  |   color:#000;
  | }
Jade
            )
        );
        $this->assertEquals(
            "<style type=\"text/css\">\n  body {color:#000;}\n</style>",
            $this->parse(<<<Jade
:style
  | body {color:#000;}
Jade
            )
        );

        $jade = <<<Jade
body
  p
    link:css( type="text/css", src="/css/ie6.css" )
    :style
      | img, div, a, input {
      |     behavior: url(/css/iepngfix.htc);
      | }
Jade;
        $html = <<<HTML
<body>
  <p>
    <link rel="stylesheet" type="text/css" src="/css/ie6.css" />
    <style type="text/css">
      img, div, a, input {
          behavior: url(/css/iepngfix.htc);
      }
    </style>
  </p>
</body>
HTML;
        $this->assertEquals($html, $this->parse($jade));

        $jade = <<<Jade
body
  p
    link:css( type="text/css", src="/css/ie6.css" )
    :style
      | img, div, a, input {
      |     behavior: url(/css/iepngfix.htc);
      | }
  p
    script:js( src="/js/html5.js" )
Jade;
        $html = <<<HTML
<body>
  <p>
    <link rel="stylesheet" type="text/css" src="/css/ie6.css" />
    <style type="text/css">
      img, div, a, input {
          behavior: url(/css/iepngfix.htc);
      }
    </style>
  </p>
  <p>
    <script type="text/javascript" src="/js/html5.js"></script>
  </p>
</body>
HTML;
        $this->assertEquals($html, $this->parse($jade));

        $jade = <<<Jade
head
  /[if lt IE 7]
    link:css( type="text/css", src="/css/ie6.css" )
    :style
      | img, div, a, input {
      |     behavior: url(/css/iepngfix.htc);
      | }

  /[if lt IE 9]
    script:js( src="/js/html5.js" )
Jade;
        $html = <<<HTML
<head>
  <!--[if lt IE 7]>
    <link rel="stylesheet" type="text/css" src="/css/ie6.css" />
    <style type="text/css">
      img, div, a, input {
          behavior: url(/css/iepngfix.htc);
      }
    </style>
  <![endif]-->
  <!--[if lt IE 9]>
    <script type="text/javascript" src="/js/html5.js"></script>
  <![endif]-->
</head>
HTML;
        $this->assertEquals($html, $this->parse($jade));
    }

    public function testPHPFilter()
    {
        $this->assertEquals(
            "<?php\n  \$bar = 10;\n  \$bar++;\n  echo \$bar;\n?>",
            $this->parse(<<<Jade
:php
  | \$bar = 10;
  | \$bar++;
  | echo \$bar;
Jade
            )
        );
    }
}