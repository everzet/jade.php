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
            "<script type=\"text/javascript\">\n//<![CDATA[\nvar name = \"<?php echo \$name ?>\";\n//]]>\n</script>",
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
            "<![CDATA[\nfoo\n]]>",
            $this->parse(<<<Jade
:cdata
  | foo
Jade
            )
        );
        $this->assertEquals(
            "<![CDATA[\nfoo\nbar\n]]>",
            $this->parse(<<<Jade
:cdata
  | foo
  | bar
Jade
            )
        );
        $this->assertEquals(
            "<![CDATA[\nfoo\nbar\n]]>\n<p>something else</p>",
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
            "<script type=\"text/javascript\">\n//<![CDATA[\nalert('foo')\n//]]>\n</script>",
            $this->parse(<<<Jade
:javascript
  | alert('foo')
Jade
            )
        );
    }

    public function testPHPFilter()
    {
        $this->assertEquals(
            "<?php\n\$bar = 10;\n\$bar++;\necho \$bar;\n?>",
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