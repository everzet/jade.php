<?php

namespace Everzet\HTAML\Tokens;

use \Everzet\HTAML\Parser;
use \Everzet\HTAML\Tokens\Token;
use \Everzet\HTAML\Tokens\Tag;

class Id extends Token
{
    public static function regexp()
    {
        return "/^#([\w-]+)/";
    }

    public function parse(Parser $parser)
    {
        $token = $parser->next();
        $parser->deferToken(new Tag($parser->getLineNo(), 'div'));
        $parser->deferToken($token);

        return $parser->peek()->parse($parser);
    }
}
