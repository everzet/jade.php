<?php

namespace Everzet\HTAML\Filters;

use \Everzet\HTAML\Filters\BaseFilterInterface;

/*
 * This file is part of the HTAML package.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Filter interface.
 *
 * @package     HTAML
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
interface BlockFilterInterface extends BaseFilterInterface
{
    public function filter($str, $indentation = 0);
}
