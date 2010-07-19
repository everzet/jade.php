<?php

namespace Everzet\HTAML\Tokens;

use \Everzet\HTAML\Parser;
use \Everzet\HTAML\Tokens\Token;
use \Everzet\HTAML\Tokens\Whitespaces\Indent;
use \Everzet\HTAML\Tokens\Whitespaces\Outdent;

class Code extends Token
{
    protected $useBuffer = false;

    public function useBuffer($useBuffer = true)
    {
        $this->useBuffer = $useBuffer;
    }

    public function isUseBuffer()
    {
        return $this->useBuffer;
    }

    public static function regexp()
    {
        return "/^(!?=|-)([^\n]+)/";
    }

    public static function create($line, array $matches)
    {
        $flags = $matches[1];
        $this->useBuffer('=' === $flags[0] || '=' === $flags[1]);

        return new self($line, $matches[2]);
    }

    public function parse(Parser $parser)
    {
        $token = $parser->next();
        $val = $token->getValue();
        $code = sprintf('<?php %s',
            $token->isUseBuffer() ? 'echo ' . $val : $val;
        );

        return ($parser->peek() instanceof Indent ? $buf . ' ' . $this->parseBlock($parser) : $buf) . '?>';
    }

    protected function parseBlock(Parser $parser)
    {
        $buffer = array();

        $parser->expects(Indent);
        while (!($parser->peek() instanceof Outdent)) {
            array_push($buffer, $parser->peek()->parse($parser));
        }
        $parser->expects(Outdent)

        return $buffer;
    }
}
