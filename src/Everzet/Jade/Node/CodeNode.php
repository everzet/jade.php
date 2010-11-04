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
 * Code Node. 
 */
class CodeNode extends Node
{
    protected $code;
    protected $buffering = false;
    protected $block;

    /**
     * Initialize code node. 
     * 
     * @param   string  $code       code string
     * @param   boolean $buffering  turn on buffering
     * @param   integer $line       source line
     */
    public function __construct($code, $buffering = false, $line)
    {
        parent::__construct($line);

        $this->code         = $code;
        $this->buffering    = $buffering;
    }

    /**
     * Return code string. 
     * 
     * @return  string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Return true if code buffered. 
     * 
     * @return  boolean
     */
    public function isBuffered()
    {
        return $this->buffering;
    }

    /**
     * Set block node. 
     * 
     * @param   BlockNode   $node   child node
     */
    public function setBlock(BlockNode $node)
    {
        $this->block = $node;
    }

    /**
     * Return block node. 
     * 
     * @return  BlockNode
     */
    public function getBlock()
    {
        return $this->block;
    }
}
