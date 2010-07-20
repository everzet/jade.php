<?php

namespace Everzet\HTAML\Filters;

use \Everzet\HTAML\Filters\Filter;

/*
 * This file is part of the HTAML package.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * CDATA filter.
 *
 * @package     HTAML
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class CDATA implements Filter
{
    public function filter($str, $indentation = 0)
    {
        return preg_replace("/\n/", "\n" . str_repeat('  ', $indentation), "<![CDATA[\n$str\n]]>");
    }
}
