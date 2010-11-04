<?php

namespace Everzet\Jade\Dumper;

use Everzet\Jade\Visitor\VisitorInterface;
use Everzet\Jade\Node\BlockNode;

/*
 * This file is part of the Jade.php.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Dumper Interface. 
 */
interface DumperInterface
{
    /**
     * Dump node to string.
     * 
     * @param   BlockNode   $node   root node
     *
     * @return  string
     */
    public function dump(BlockNode $node);

    /**
     * Register visitee extension. 
     * 
     * @param   string              $nodeName   name of the visitable node (block, code, comment, doctype, filter, tag, text)
     * @param   VisitorInterface    $visitor    visitor object
     */
    public function registerVisitor($nodeName, VisitorInterface $visitor);
}
