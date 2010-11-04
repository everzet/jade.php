<?php

namespace Everzet\Jade;

use Everzet\Jade\Exception\Exception;
use Everzet\Jade\Lexer\LexerInterface;
use Everzet\Jade\Dumper\DumperInterface;

/*
 * This file is part of the Jade.php.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Jade. 
 */
class Jade
{
    protected $parser;
    protected $document;
    protected $dumperName;
    protected $dumpers = array();

    /**
     * Initialize parser. 
     * 
     * @param   LexerInterface  $lexer  jade lexer
     */
    public function __construct(LexerInterface $lexer)
    {
        $this->parser = new Parser($lexer);
    }

    /**
     * Register specific dumper with custom name.
     * 
     * @param   string          $name       dumper name
     * @param   DumperInterface $dumper     dumper
     *
     * @throws  \InvalidArgumentException   if dumper with such name already registered
     */
    public function registerDumper($name, DumperInterface $dumper)
    {
        if (isset($this->dumpers[$name])) {
            throw new \InvalidArgumentException(sprintf('Dumper with name %s already registered.', $name));
        }

        $this->dumpers[$name] = $dumper;
    }

    /**
     * Set default dumper name.
     * 
     * @param   string  $name               dumper name
     *
     * @throws  \InvalidArgumentException   if dumper with such name not registered
     */
    public function setDefaultDumper($name)
    {
        if (!isset($this->dumpers[$name])) {
            throw new \InvalidArgumentException(sprintf('No dumper with %s name registered.', $name));
        }

        $this->dumperName = $name;
    }

    /**
     * Load & parse jade document. 
     * 
     * @param   string  $string jade document
     *
     * @throws  Exception   on parsing errors
     */
    public function load($string)
    {
        $this->document = $this->parser->parse($string);
    }

    /**
     * Load & parse jade document from file. 
     * 
     * @param   string  $filename   jade document file path
     */
    public function loadFile($filename)
    {
        $this->load(file_get_contents($filename));
    }

    /**
     * Dump jade document with current dumper & return dumped string. 
     * 
     * @param   string  $dumperName dumper name (will use default if no provided)
     *
     * @return  string
     *
     * @throws  Exception           if dumper is not specified
     */
    public function dump($dumperName = null)
    {
        $dumperName = null !== $dumperName ? $dumperName : $this->dumperName;

        if (null === $dumperName) {
            throw new Exception('You must specify dumper name');
        }

        return $this->dumpers[$dumperName]->dump($this->document);
    }

    /**
     * Dump jade document with current dumper to specified file. 
     * 
     * @param   string  $path       dumping file path
     * @param   string  $dumperName dumper name (will use default if no provided)
     */
    public function dumpToFile($path, $dumperName = null)
    {
        file_put_contents($path, $this->dump(), $dumperName);
    }
}
