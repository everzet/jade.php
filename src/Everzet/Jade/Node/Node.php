<?php

namespace Everzet\Jade\Node;

/*
 * This file is part of the Jade.php.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Node. 
 */
abstract class Node
{
    protected $line;

    /**
     * Initialize node. 
     * 
     * @param   integer $line   source line
     */
    public function __construct($line)
    {
        $this->line = $line;
    }

    /**
     * Return node source line. 
     * 
     * @return  integer
     */
    public function getLine()
    {
        return $this->line;
    }
}
