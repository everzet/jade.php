<?php

namespace Everzet\Jade\Filters;

use \Everzet\Jade\Filters\BaseFilterInterface;

/*
 * This file is part of the Jade package.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * TextFilter interface.
 *
 * @package     Jade
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
interface TextFilterInterface extends BaseFilterInterface
{
    public function filterText($str);
}
