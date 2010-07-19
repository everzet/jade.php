<?php

namespace Everzet\HTAML\Tokens;

use \Everzet\HTAML\Tokens\Token;

class Tag extends Token
{
    protected $selfClosing = array(
        'meta', 'img', 'link', 'br', 'hr', 'input', 'area', 'base'
    );

    public static function regexp()
    {
        return "/^(\w[:\w]*)/";
    }
}
