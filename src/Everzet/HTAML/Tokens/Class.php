<?php

namespace Everzet\HTAML\Tokens;

use \Everzet\HTAML\Tokens\Id;

class Class extends Id
{
    public static function regexp()
    {
        return "/^\.([\w-]+)/";
    }
}
