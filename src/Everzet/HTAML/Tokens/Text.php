<?php

namespace Everzet\HTAML\Tokens;

use \Everzet\HTAML\Parser;
use \Everzet\HTAML\Tokens\Token;

class Text extends Token
{
    public static function regexp()
    {
        return "/^(?:\| ?)?([^\n]+)/";
    }

    public function parse(Parser $parser)
    {
        return $parser->next()->getValue();
    }
}
