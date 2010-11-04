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
 * Tag Node. 
 */
class TagNode extends BlockNode
{
    protected $name;
    protected $attributes = array('id' => false);
    protected $text;
    protected $code;

    /**
     * Initialize tag node. 
     * 
     * @param   string  $name   tag name
     * @param   integer $line   source line
     */
    public function __construct($name, $line)
    {
        parent::__construct($line);

        $this->name = $name;
    }

    /**
     * Set tag name. 
     * 
     * @param   string  $name   tag name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Return tag name. 
     * 
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set tag attribute to value. 
     * 
     * @param   string  $key    attribute name
     * @param   string  $value  attribute value
     */
    public function setAttribute($key, $value)
    {
        if ('class' === $key) {
            if (!isset($this->attributes[$key])) {
                $this->attributes[$key] = array();
            }

            $this->attributes[$key][] = $value;
        } else {
            $this->attributes[$key]  = $value;
        }
    }

    /**
     * Return all attributes. 
     * 
     * @return  array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set inner text node. 
     * 
     * @param   TextNode    $node   inner text
     */
    public function setText(TextNode $node)
    {
        $this->text = $node;
    }

    /**
     * Return inner text node. 
     * 
     * @return  TextNode|null
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set inner code node. 
     * 
     * @param   CodeNode    $node   inner code
     */
    public function setCode(CodeNode $node)
    {
        $this->code = $node;
    }

    /**
     * Return inner code node. 
     * 
     * @return  CodeNode
     */
    public function getCode()
    {
        return $this->code;
    }
}
