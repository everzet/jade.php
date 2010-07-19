<?php

namespace Everzet\HTAML\Tokens;

use \Everzet\HTAML\Tokens\Token;
use \Everzet\HTAML\Filters\Filter;
use \Everzet\HTAML\Parser;
use \Everzet\HTAML\ParserException;
use \Everzet\HTAML\Tokens\Text;
use \Everzet\HTAML\Tokens\Whitespaces\Indent;
use \Everzet\HTAML\Tokens\Whitespaces\Outdent;
use \Everzet\HTAML\Tokens\Whitespaces\Newline;

class Filter extends Token
{
    protected $filters = array(
        'cdata'         => "\\Everzet\\HTAML\\Filters\\CDATA",
        'javascript'    => "\\Everzet\\HTAML\\Filters\\JavaScript"
    );

    public static function regexp()
    {
        return "/^:(\w+)/";
    }

    public function parse(Parser $parser)
    {
        $name = $parser->expects(self)->getValue();
        if (isset($this->filters[$name])) {
            $class = $this->filters[$name];
            return $class::filter($this->parseTextBlock());
        } else {
            throw new ParserException(sprintf('Unknown filter ":%s".', $name));
        }
    }

    protected function parseTextBlock(Parser $parser)
    {
        $buffer = array();

        $parser->expects(Indent);
        while ($parser->peek() instanceof Text || $parser->peek() instanceof Newline) {
            if ($parser->peek() instanceof Newline) {
                $parser->next();
                array_push($buffer, "\n");
            } else {
                array_push($buffer, $parser->next()->getValue());
            }
        }
        $parser->expects(Outdent);

        return implode('', $buffer);
    }
}
