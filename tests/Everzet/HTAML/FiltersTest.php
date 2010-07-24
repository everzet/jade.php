<?php

use \Everzet\HTAML\Parser;

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
            $this->parse(<<<HTAML
:javascript
  | var name = "{{\$name}}";
HTAML
            )
        );
    }

    public function testCDATAFilter()
    {
        $this->assertEquals(
            "<![CDATA[\nfoo\n]]>",
            $this->parse(<<<HTAML
:cdata
  | foo
HTAML
            )
        );
        $this->assertEquals(
            "<![CDATA[\nfoo\nbar\n]]>",
            $this->parse(<<<HTAML
:cdata
  | foo
  | bar
HTAML
            )
        );
        $this->assertEquals(
            "<![CDATA[\nfoo\nbar\n]]>\n<p>something else</p>",
            $this->parse(<<<HTAML
:cdata
  | foo
  | bar
p something else
HTAML
            )
        );
    }

    public function testJavaScriptFilter()
    {
        $this->assertEquals(
            "<script type=\"text/javascript\">\n//<![CDATA[\nalert('foo')\n//]]>\n</script>",
            $this->parse(<<<HTAML
:javascript
  | alert('foo')
HTAML
            )
        );
    }

    public function testPHPFilter()
    {
        $this->assertEquals(
            "<?php\n\$bar = 10;\n\$bar++;\necho \$bar;\n?>",
            $this->parse(<<<HTAML
:php
  | \$bar = 10;
  | \$bar++;
  | echo \$bar;
HTAML
            )
        );
    }
}