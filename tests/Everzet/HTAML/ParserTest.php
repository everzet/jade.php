<?php

use \Everzet\HTAML\Parser;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    protected function parse($value)
    {
        $parser = new Parser($value);
        return $parser->parse();
    }

    public function testBasicIndent()
    {
        $htaml = <<<HTAML
!!! 5
html
  head
    title Test
  body
    h1#SUPER.inco(class='header one', style='width:350px;') Test
    p(selected) Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
HTAML;
        $html = <<<HTML
<!DOCTYPE html>
<html>
  <head>
    <title>Test</title>
  </head>
  <body>
    <h1 id="SUPER" style="width:350px;" class="inco header one">Test</h1>
    <p selected="selected">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
  </body>
</html>
HTML;

        $this->assertEquals($html, $this->parse($htaml));
    }

    public function testComplexTemplate()
    {
        $htaml = <<<HTAML
!!! strict
html
  head
    title Test
  body
    h1 Test
    - if ('Ivan' === \$name)
      h2
        -header()
        a as
          span= \$name
        span- \$blog
    - else
      span= \$name
      h3 40
    p Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
HTAML;
        $html = <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
  <head>
    <title>Test</title>
  </head>
  <body>
    <h1>Test</h1>
    <?php if ('Ivan' === \$name): ?>
      <h2>
        <?php header() ?>
        <a>
          as
          <span><?php echo \$name ?></span>
        </a>
        <span><?php \$blog ?></span>
      </h2>
    <?php else: ?>
      <span><?php echo \$name ?></span>
      <h3>40</h3>
    <?php endif; ?>
    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
  </body>
</html>
HTML;

        $this->assertEquals($html, $this->parse($htaml));
    }

    
}
