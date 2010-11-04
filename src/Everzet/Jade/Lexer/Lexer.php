<?php

namespace Everzet\Jade\Lexer;

use Everzet\Jade\Exception\Exception;

/*
 * This file is part of the Jade.php.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Jade Lexer. 
 */
class Lexer implements LexerInterface
{
    protected $input;
    protected $deferredObjects   = array();
    protected $lastIndents      = 0;
    protected $lineno           = 1;
    protected $stash            = array();

    /**
     * Set lexer input. 
     * 
     * @param   string  $input  input string
     */
    public function setInput($input)
    {
        $this->input            = preg_replace(array('/\r\n|\r/', '/\t/'), array("\n", '  '), $input);
        $this->deferredObjects  = array();
        $this->lastIndents      = 0;
        $this->lineno           = 1;
        $this->stash            = array();
    }

    /**
     * Return next token or previously stashed one. 
     * 
     * @return  Object
     */
    public function getAdvancedToken()
    {
        if ($token = $this->getStashedToken()) {
            return $token;
        }

        return $this->getNextToken();
    }

    /**
     * Return current line number. 
     * 
     * @return  integer
     */
    public function getCurrentLine()
    {
        return $this->lineno;
    }

    /**
     * Defer token. 
     * 
     * @param   Object   $token  token to defer
     */
    public function deferToken(\stdClass $token)
    {
        $this->deferredObjects[] = $token;
    }

    /**
     * Predict for number of tokens. 
     * 
     * @param   integer     $number number of tokens to predict
     *
     * @return  Object              predicted token
     */
    public function predictToken($number = 1)
    {
        $fetch = $number - count($this->stash);

        while ($fetch-- > 0) {
            $this->stash[] = $this->getNextToken();
        }

        return $this->stash[--$number];
    }

    /**
     * Construct token with specified parameters. 
     * 
     * @param   string  $type   token type
     * @param   string  $value  token value
     *
     * @return  Object          new token object
     */
    public function takeToken($type, $value = null)
    {
        return (Object) array(
            'type'  => $type
          , 'line'  => $this->lineno
          , 'value' => $value
        );
    }

    /**
     * Return stashed token. 
     * 
     * @return  Object|boolean   token if has stashed, false otherways
     */
    protected function getStashedToken()
    {
        return count($this->stash) ? array_shift($this->stash) : null;
    }

    /**
     * Return deferred token. 
     * 
     * @return  Object|boolean   token if has deferred, false otherways
     */
    protected function getDeferredToken()
    {
        return count($this->deferredObjects) ? array_shift($this->deferredObjects) : null;
    }

    /**
     * Return next token. 
     * 
     * @return  Object
     */
    protected function getNextToken()
    {
        $scanners = array(
            'getDeferredToken'
          , 'scanEOS'
          , 'scanTag'
          , 'scanFilter'
          , 'scanCode'
          , 'scanDoctype'
          , 'scanId'
          , 'scanClass'
          , 'scanAttributes'
          , 'scanIndentation'
          , 'scanComment'
          , 'scanText'
        );

        foreach ($scanners as $scan) {
            $token = $this->$scan();

            if (null !== $token && $token) {
                return $token;
            }
        }
    }

    /**
     * Consume input. 
     * 
     * @param   integer $length length of input to consume
     */
    protected function consumeInput($length)
    {
        $this->input = mb_substr($this->input, $length);
    }

    /**
     * Scan for token with specified regex. 
     * 
     * @param   string  $regex  regular expression
     * @param   string  $type   expected token type
     *
     * @return  Object|null
     */
    protected function scanInput($regex, $type)
    {
        $matches = array();
        if (preg_match($regex, $this->input, $matches)) {
            $this->consumeInput(mb_strlen($matches[0]));

            return $this->takeToken($type, $matches[1]);
        }
    }

    /**
     * Scan EOS from input & return it if found.
     * 
     * @return  Object|null
     */
    protected function scanEOS()
    {
        if (mb_strlen($this->input)) {
            return;
        }

        return $this->lastIndents-- > 0 ? $this->takeToken('outdent') : $this->takeToken('eos');
    }

    /**
     * Scan comment from input & return it if found. 
     * 
     * @return  Object|null
     */
    protected function scanComment()
    {
        $matches = array();

        if (preg_match('/^ *\/\/(-)?([^\n]+)?/', $this->input, $matches)) {
            $this->consumeInput(mb_strlen($matches[0]));
            $token = $this->takeToken('comment', isset($matches[2]) ? $matches[2] : '');
            $token->buffer = !isset($matches[1]) || '-' !== $matches[1];

            return $token;
        }
    }

    /**
     * Scan tag from input & return it if found. 
     * 
     * @return  Object|null
     */
    protected function scanTag()
    {
        return $this->scanInput('/^(\w[:-\w]*)/', 'tag');
    }

    /**
     * Scan tag from input & return it if found. 
     * 
     * @return  Object|null
     */
    protected function scanFilter()
    {
        return $this->scanInput('/^:(\w+)/', 'filter');
    }

    /**
     * Scan doctype from input & return it if found. 
     * 
     * @return  Object|null
     */
    protected function scanDoctype()
    {
        return $this->scanInput('/^!!! *(\w+)?/', 'doctype');
    }

    /**
     * Scan id from input & return it if found. 
     * 
     * @return  Object|null
     */
    protected function scanId()
    {
        return $this->scanInput('/^#([\w-]+)/', 'id');
    }

    /**
     * Scan class from input & return it if found. 
     * 
     * @return  Object|null
     */
    protected function scanClass()
    {
        return $this->scanInput('/^\.([\w-]+)/', 'class');
    }

    /**
     * Scan text from input & return it if found. 
     * 
     * @return  Object|null
     */
    protected function scanText()
    {
        return $this->scanInput('/^(?:\|)? ?([^\n]+)/', 'text');
    }

    /**
     * Scan code from input & return it if found. 
     * 
     * @return  Object|null
     */
    protected function scanCode()
    {
        $matches = array();

        if (preg_match('/^(!?=|-)([^\n]+)/', $this->input, $matches)) {
            $this->consumeInput(mb_strlen($matches[0]));

            $flags = $matches[1];
            $token = $this->takeToken('code', $matches[2]);
            $token->buffer = (isset($flags[0]) && '=' === $flags[0]) || (isset($flags[1]) && '=' === $flags[1]);

            return $token;
        }
    }

    /**
     * Scan attributes from input & return them if found. 
     * 
     * @return  Object|null
     */
    protected function scanAttributes()
    {
        if ('(' === $this->input[0]) {
            $index      = $this->getDelimitersIndex('(', ')');
            $input      = mb_substr($this->input, 1, $index - 1);
            $token      = $this->takeToken('attributes', $input);
            $attributes = preg_split('/ *, *(?=[\'"\w-]+ *[:=]|[\w-]+ *$)/', $token->value);
            $this->consumeInput($index + 1);
            $token->attributes = array();

            foreach ($attributes as $i => $pair) {
                $pair = preg_replace('/^ *| *$/', '', $pair);
                $colon = mb_strpos($pair, ':');
                $equal = mb_strpos($pair, '=');

                $sbrac = mb_strpos($pair, '\'');
                $dbrac = mb_strpos($pair, '"');
                if ($sbrac < 1) {
                    $sbrac = false;
                }
                if ($dbrac < 1) {
                    $dbrac = false;
                }
                if ((false !== $sbrac && $colon > $sbrac) || (false !== $dbrac && $colon > $dbrac)) {
                    $colon = false;
                }
                if ((false !== $sbrac && $equal > $sbrac) || (false !== $dbrac && $equal > $dbrac)) {
                    $equal = false;
                }

                if (false === $colon && false === $equal) {
                    $key    = $pair;
                    $value  = true;
                } else {
                    $splitter = false !== $colon ? $colon : $equal;

                    if (false !== $colon && $colon < $equal) {
                        $splitter = $colon;
                    }

                    $key    = mb_substr($pair, 0, $splitter);
                    $value  = preg_replace('/^ *[\'"]?|[\'"]? *$/', '', mb_substr($pair, ++$splitter, mb_strlen($pair)));

                    if ('true' === $value) {
                        $value = true;
                    } elseif (empty($value) || 'null' === $value || 'false' === $value) {
                        $value = false;
                    }
                }

                $token->attributes[preg_replace(array('/^ +| +$/', '/^[\'"]|[\'"]$/'), '', $key)] = $value;
            }

            return $token;
        }
    }

    /**
     * Scan indentation from input & return it if found. 
     * 
     * @return  Object|null
     */
    protected function scanIndentation()
    {
        $matches = array();

        if (preg_match('/^\n( *)/', $this->input, $matches)) {
            $this->lineno++;
            $this->consumeInput(mb_strlen($matches[0]));

            $token      = $this->takeToken('indent', $matches[1]);
            $indents    = mb_strlen($token->value) / 2;


            if (mb_strlen($this->input) && "\n" === $this->input[0]) {
                $token->type = 'newline';

                return $token;
            } elseif (0 !== $indents % 1) {
                throw new Exception(sprintf(
                    'Invalid indentation found. Spaces count must be a multiple of two, but %d got.'
                  , mb_strlen($token->value)
                ));
            } elseif ($this->lastIndents === $indents) {
                $token->type = 'newline';
            } elseif ($this->lastIndents + 1 < $indents) {
                throw new Exception(sprintf(
                    'Invalid indentation found. Got %d, but expected %d.'
                  , $indents
                  , $this->lastIndents + 1
                ));
            } elseif ($this->lastIndents > $indents) {
                $count = $this->lastIndents - $indents;
                $token->type = 'outdent';
                while (--$count) {
                    $this->deferToken($this->takeToken('outdent'));
                }
            }

            $this->lastIndents = $indents;

            return $token;
        }
    }

    /**
     * Return the index of begin/end delimiters. 
     * 
     * @param   string  $begin  befin delimiter
     * @param   string  $end    end delimiter
     *
     * @return  integer         position index
     */
    protected function getDelimitersIndex($begin, $end)
    {
        $string     = $this->input;
        $nbegin     = 0;
        $nend       = 0;
        $position   = 0;

        $sbrac      = false;
        $dbrac      = false;

        for ($i = 0, $length = mb_strlen($string); $i < $length; ++$i) {
            if ('"' === $string[$i]) {
                $dbrac = !$dbrac;
            } elseif ('\'' === $string[$i]) {
                $sbrac = !$sbrac;
            }

            if (!$sbrac && !$dbrac && $begin === $string[$i]) {
                ++$nbegin;
            } elseif (!$sbrac && !$dbrac && $end === $string[$i]) {
                if (++$nend === $nbegin) {
                    $position = $i;
                    break;
                }
            }
        }

        return $position;
    }
}
