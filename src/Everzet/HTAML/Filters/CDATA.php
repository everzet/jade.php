<?php

namespace Everzet\HTAML\Filters;

use \Everzet\HTAML\Filters\Filter;

class CDATA implements Filter
{
    public static function filter($str)
    {
        return '<![CDATA[\\n' . $str . '\\n]]>';
    }
}
