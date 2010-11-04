<?php

namespace Everzet\Jade\Filter;

/*
 * This file is part of the Jade.php.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * JavaScript script tag filter. 
 */
class JavaScriptFilter implements FilterInterface
{
    /**
     * Filter text. 
     * 
     * @param   string  $text       text to filter
     * @param   array   $attributes filter options from template
     * @param   string  $indent     indentation string
     *
     * @return  string              filtered text
     */
    public function filter($text, array $attributes, $indent)
    {
        $html  = $indent . '<script type="text/javascript">' . "\n";
        $html .= $text;
        $html .= "\n" . $indent . '</script>';

        return $html;
    }
}
