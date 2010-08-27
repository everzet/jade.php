<?php

namespace Everzet\Jade\Filters;

use \Everzet\Jade\Filters\BlockFilterInterface;

/*
 * This file is part of the Jade package.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * JavaScript filter.
 *
 * @package     Jade
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class JavaScript implements BlockFilterInterface
{
    public function filter($str)
    {
        // Add block indentation
        $str = preg_replace("/\n/", "\n  ", "\n" . $str);

        return sprintf("<script type=\"text/javascript\">%s\n</script>", $str);
    }
}
