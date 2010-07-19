<?php

namespace Everzet\HTAML\Tokens;

/*
 * This file is part of the behat package.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * HTAML Token.
 *
 * @package     HTAML
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
abstract class Token
{
    protected $line;
    protected $value;

    public function __construct($line, $value = '')
    {
        $this->line = $line;
        $this->value = $value;
    }

    public function getLine()
    {
        return $this->line;
    }

    public function getValue()
    {
        return $this->value;
    }

    public static function create($line, array $matches)
    {
        $class = get_called_class();

        return new $class($line, $matches[1]);
    }
}
