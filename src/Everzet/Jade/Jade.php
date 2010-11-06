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
    protected $dumper;
    protected $cache;

    /**
     * Initialize parser. 
     * 
     * @param   LexerInterface  $lexer  jade lexer
     */
    public function __construct(Parser $parser, DumperInterface $dumper, $cache = null)
    {
        $this->parser = $parser;
        $this->dumper = $dumper;
        $this->cache  = $cache;
    }

    /**
     * Render provided input to dumped string. 
     * 
     * @param   string  $input  input string or file path
     *
     * @return  string          dumped string
     */
    public function render($input)
    {
        $source = $this->getInputSource($input);
        $parsed = $this->parser->parse($source);

        return $this->dumper->dump($parsed);
    }

    /**
     * Get current fresh cache path or render & dump input to new cache & return it's path.
     * 
     * @param   string  $input  input string or file path
     *
     * @return  string          cache path
     */
    public function cache($input)
    {
        if (null === $this->cache || !is_dir($this->cache)) {
            throw new Exception('You must provide correct cache path to Jade for caching.');
        }

        $cacheKey   = $this->getInputCacheKey($input);
        $cacheTime  = $this->getCacheTime($cacheKey);

        if (false !== $cacheTime && $this->isCacheFresh($input, $cacheTime)) {
            return $this->getCachePath($cacheKey);
        }

        if (!is_writable($this->cache)) {
            throw new Exception(sprintf('Cache directory must be writable. "%s" is not.', $this->cache));
        }

        $rendered = $this->render($input);

        return $this->cacheInput($cacheKey, $rendered);
    }

    /**
     * Return source from input (Jade template). 
     * 
     * @param   string  $input  input string or file path
     *
     * @return  string
     */
    protected function getInputSource($input)
    {
        if (is_file($input)) {
            return file_get_contents($input);
        }

        return (string) $input;
    }

    /**
     * Return caching key for input. 
     * 
     * @param   string  $input  input string or file path
     *
     * @return  string
     */
    protected function getInputCacheKey($input)
    {
        if (is_file($input)) {
            return basename($input, '.jade');
        } else {
            throw new \InvalidArgumentException('Only file templates can be cached.');
        }
    }

    /**
     * Return full cache path for specified cache key. 
     * 
     * @param   string  $cacheKey   cache key
     *
     * @return  string              absolute path
     */
    protected function getCachePath($cacheKey)
    {
        return $this->cache . '/' . $cacheKey . '.php';
    }

    /**
     * Return cache time creation. 
     * 
     * @param   string  $cacheKey   cache key
     *
     * @return  integer             UNIX timestamp (filemtime used)
     */
    protected function getCacheTime($cacheKey)
    {
        $path = $this->getCachePath($cacheKey);

        if (is_file($path)) {
            return filemtime($path);
        }

        return false;
    }

    /**
     * Return true if cache, created at specified time is fresh enough for provided input. 
     * 
     * @param   string  $input      input string or file
     * @param   srting  $cacheTime  cache key
     *
     * @return  boolean             true if fresh, false otherways
     */
    protected function isCacheFresh($input, $cacheTime)
    {
        if (is_file($input)) {
            return filemtime($input) < $cacheTime;
        }

        return false;
    }

    /**
     * Cache rendered input at provided key. 
     * 
     * @param   string  $cacheKey   new cache key
     * @param   string  $rendered   rendered input
     *
     * @return  string              new cache path
     */
    protected function cacheInput($cacheKey, $rendered)
    {
        $path = $this->getCachePath($cacheKey);

        file_put_contents($path, $rendered);

        return $path;
    }
}
