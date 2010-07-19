<?php

namespace Everzet\HTAML\Filters;

use \Everzet\HTAML\Filters\Filter;

class JavaScript implements Filter
{
    public static function filter($str)
    {
        return '<script type="text/javascript">\\n//<![CDATA[\\n' . $str . '\\n//]]></script>';
    }
}
