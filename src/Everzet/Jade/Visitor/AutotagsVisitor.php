<?php

namespace Everzet\Jade\Visitor;

use Everzet\Jade\Node\Node;
use Everzet\Jade\Node\TagNode;

/*
 * This file is part of the Jade.php.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Autotags Replacer. 
 */
class AutotagsVisitor implements VisitorInterface
{
    protected $autotags = array(
        'a:void'                => array('tag' => 'a',      'attrs' => array('href' => 'javascript:void(0)')),
        'form:post'             => array('tag' => 'form',   'attrs' => array('method' => 'POST')),
        'link:css'              => array('tag' => 'link',   'attrs' => array('rel' => 'stylesheet', 'type'  => 'text/css')),
        'script:js'             => array('tag' => 'script', 'attrs' => array('type'  => 'text/javascript')),
        'input:button'          => array('tag' => 'input',  'attrs' => array('type' => 'button')),
        'input:checkbox'        => array('tag' => 'input',  'attrs' => array('type' => 'checkbox')),
        'input:file'            => array('tag' => 'input',  'attrs' => array('type' => 'file')),
        'input:hidden'          => array('tag' => 'input',  'attrs' => array('type' => 'hidden')),
        'input:image'           => array('tag' => 'input',  'attrs' => array('type' => 'image')),
        'input:password'        => array('tag' => 'input',  'attrs' => array('type' => 'password')),
        'input:radio'           => array('tag' => 'input',  'attrs' => array('type' => 'radio')),
        'input:reset'           => array('tag' => 'input',  'attrs' => array('type' => 'reset')),
        'input:submit'          => array('tag' => 'input',  'attrs' => array('type' => 'submit')),
        'input:text'            => array('tag' => 'input',  'attrs' => array('type' => 'text')),
        'input:search'          => array('tag' => 'input',  'attrs' => array('type' => 'search')),
        'input:tel'             => array('tag' => 'input',  'attrs' => array('type' => 'tel')),
        'input:url'             => array('tag' => 'input',  'attrs' => array('type' => 'url')),
        'input:email'           => array('tag' => 'input',  'attrs' => array('type' => 'email')),
        'input:datetime'        => array('tag' => 'input',  'attrs' => array('type' => 'datetime')),
        'input:date'            => array('tag' => 'input',  'attrs' => array('type' => 'date')),
        'input:month'           => array('tag' => 'input',  'attrs' => array('type' => 'month')),
        'input:week'            => array('tag' => 'input',  'attrs' => array('type' => 'week')),
        'input:time'            => array('tag' => 'input',  'attrs' => array('type' => 'time')),
        'input:number'          => array('tag' => 'input',  'attrs' => array('type' => 'number')),
        'input:range'           => array('tag' => 'input',  'attrs' => array('type' => 'range')),
        'input:color'           => array('tag' => 'input',  'attrs' => array('type' => 'color')),
        'input:datetime-local'  => array('tag' => 'input',  'attrs' => array('type'  => 'datetime-local'))
    );

    /**
     * Visit node. 
     * 
     * @param   Node    $node   node to visit
     */
    public function visit(Node $node)
    {
        if (!($node instanceof TagNode)) {
            throw new \InvalidArgumentException(sprintf('Autotags filter may only work with tag nodes, but %s given', get_class($node)));
        }

        if (isset($this->autotags[$node->getName()])) {
            foreach ($this->autotags[$node->getName()]['attrs'] as $key => $value) {
                $node->setAttribute($key, $value);
            }

            $node->setName($this->autotags[$node->getName()]['tag']);
        }
    }
}
