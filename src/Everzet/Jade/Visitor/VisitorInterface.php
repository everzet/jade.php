<?php

namespace Everzet\Jade\Visitor;

use Everzet\Jade\Node\Node;

/*
 * This file is part of the Jade.php.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Node Visitor Interface. 
 */
interface VisitorInterface
{
    /**
     * Visit node. 
     * 
     * @param   Node    $node   node to visit
     */
    public function visit(Node $node);
}
