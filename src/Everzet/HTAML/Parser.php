<?php

namespace Everzet\HTAML;

use \Everzet\HTAML\ParserException;
use \Everzet\HTAML\Tokens\Token;
use \Everzet\HTAML\Tokens\Whitespaces\EOS;
use \Everzet\HTAML\Tokens\Whitespaces\Newline;
use \Everzet\HTAML\Tokens\Whitespaces\Indent;
use \Everzet\HTAML\Tokens\Whitespaces\Outdent;

/*
 * This file is part of the behat package.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * HTAML Parser.
 *
 * @package     HTAML
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class Parser
{
    protected $tokenTypes = array(
        "\\Everzet\\HTAML\\Tokens\\Tag",
        "\\Everzet\\HTAML\\Tokens\\Filter",
        "\\Everzet\\HTAML\\Tokens\\Code",
        "\\Everzet\\HTAML\\Tokens\\Doctype",
        "\\Everzet\\HTAML\\Tokens\\Id",
        "\\Everzet\\HTAML\\Tokens\\Class",
        "\\Everzet\\HTAML\\Tokens\\Attrs",
        "\\Everzet\\HTAML\\Tokens\\Text"
    );

    protected $input;
    protected $filename;
    protected $deferredTokens = array();
    protected $lastIndents = 0;
    protected $lineno = 1;
    protected $stash = null;
    protected $mode;

    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    public function __construct($str, $filename)
    {
        $this->input            = preg_replace("/\r\n|\r\g/", "\n", $str);
        $this->filename         = $filename;
        $this->deferredTokens   = array();
        $this->lastIndents      = 0;
        $this->lineno           = 1;
    }

    public function next()
    {
        $matches = array();

        if (null !== $this->stash) {
            $tok = $this->stash;
            $this->stash = null;
            return $tok;
        }

        if (count($this->deferredTokens)) {
            return array_shift($this->deferredTokens);
        }

        // EOS
        if (!mb_strlen($this->input)) {
            if ($this->lastIndents-- > 0) {
                return new Outdent($this->lineno);
            } else {
                return new EOS();
            }
        }

        // Read Token's
        foreach ($tokenTypes as $tokenClass) {
            if (preg_match($tokenClass::regexp(), $this->input, $matches)) {
                $this->input = mb_substr($this->input, mb_strlen($matches[0]));
                return $tokenClass::create($this->lineno, $matches);
            }
        }

        // Indent
        if (preg_match("/^\n( *)/", $this->input, $matches)) {
            ++$this->lineno;
            $this->input = mb_substr($this->input, mb_strlen($matches[0]));
            $tok = Indent::create($this->lineno, $matches);
            $indents = mb_strlen($tok->getValue()) / 2;

            if ("\n" === $this->input[0]) {
                return new Newline($this->lineno, $matches);
            } elseif (0 !== $indents % 1) {
                throw new ParserException(sprintf(
                    'Invalid indentation, got %d space%s, must be multiple or two.',
                    mb_strlen($tok->getValue()),
                    mb_strlen($tok->getValue()) > 1 ? 's' : ''
                ));
            } elseif ($indents === $this->lastIndents) {
                $tok = new Newline($this->lineno, $matches);
            } elseif ($indents > $this->lastIndents + 1) {
                throw new ParserException(sprintf(
                    'Invalid indentation, got %d expected %d.',
                    $indents, ($this->lastIndents + 1)
                ));
            } elseif ($indents < $this->lastIndents) {
                $num = $this->lastIndents - $indents;
                $tok = new Outdent($this->lineno, $matches);
                while (--n) {
                    array_push($this->deferredTokens,
                        new Outdent($this->lineno)
                    );
                }
            }
            $this->lastIndents = $indents;
            return $tok;
        }
    }

    public function deferToken(Token $token)
    {
        array_push($this->deferredTokens,
            $token
        );
    }

    public function getLineNo()
    {
        return $this->lineno;
    }

    public function peek()
    {
        return $this->stash = $this->next();
    }

    public function expect($class)
    {
        if ($this->peek() instanceof $class) {
            return $this->next();
        } else {
            throw new ParserException(sprintf('Expected %s, but got %s.',
                $class, get_class($this->peek())
            ));
        }
    }

    public function parse()
    {
        $buffer = array();
        while (!(($token = $this->peek()) instanceof EOF)) {
            array_push($buffer, $token->parse($this));
        }

        return $buffer;
    }
}
