<?php

namespace Everzet\HTAML;

use \Everzet\HTAML\ParserException;

/*
 * This file is part of the behat package.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

# h2
# - use_helper(...)
# = link_to(...)
# :markdown
# | text

/**
 * HTAML Parser.
 *
 * @package     HTAML
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class Parser
{
    protected $selfClosing = array(
        'meta', 'img', 'link', 'br', 'hr', 'input', 'area', 'base'
    );
    protected $doctypes = array(
        '5' => '<!DOCTYPE html>',
        'xml' => '<?xml version="1.0" encoding="utf-8" ?>',
        'default' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
        'transitional' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
        'strict' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
        'frameset' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
        '1.1' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
        'basic' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">',
        'mobile' => '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.2//EN" "http://www.openmobilealliance.org/tech/DTD/xhtml-mobile12.dtd">'
    );
    protected $filters = array(
        'cdata' => "\\Everzet\\HTAML\\Filters\\CDATA",
    );

    protected $input;
    protected $deferredTokens = array();
    protected $lastIndents = 0;
    protected $lineno = 1;
    protected $stash;
    protected $mode;

    public function __construct($str)
    {
        $this->input = preg_replace("/\r\n|\r/", "\n", $str);
        $this->deferredTokens = array();
        $this->lastIndents = 0;
        $this->lineno = 1;
    }

    protected function token($type, array $matches = array('', ''))
    {
        $this->input = mb_substr($this->input, mb_strlen($matches[0]));
        return (object) array(
            'type'  => $type,
            'line'  => $this->lineno,
            'val'   => $matches[1]
        );
    }

    public function advance()
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
            if (0 < $this->lastIndents--) {
                return (object) array('type' => 'outdent', 'line' => $this->lineno);
            } else {
                return (object) array('type' => 'eos', 'line' => $this->lineno);
            }
        }

        // Tag
        if (preg_match("/^(\w[:\w]*)/", $this->input, $matches)) {
            return $this->token('tag', $matches);
        }

        // Filter
        if (preg_match("/^:(\w+)/", $this->input, $matches)) {
            return $this->token('filter', $matches);
        }

        // Code
        if (preg_match("/^(!?=|-)([^\n]+)/", $this->input, $matches)) {
            $flags = $matches[1];
            $matches[1] = $matches[2];
            $tok = $this->token('code', $matches);
            $tok->buffer = 
                (isset($flags[0]) && '=' === $flags[0]) || (isset($flags[1]) && '=' === $flags[1]);
            return $tok;
        }

        // Doctype
        if (preg_match("/^!!! *(\w+)?/", $this->input, $matches)) {
            return $this->token('doctype', $matches);
        }

        // Id
        if (preg_match("/^#([\w-]+)/", $this->input, $matches)) {
            return $this->token('id', $matches);
        }

        // Class
        if (preg_match("/^\.([\w-]+)/", $this->input, $matches)) {
            return $this->token('class', $matches);
        }

        // Attributes
        if (preg_match("/^\( *(.+) *\)/", $this->input, $matches)) {
            $tok = $this->token('attrs', $matches);
            $attrs = preg_split("/ *, *(?=[\w-]+ *[:=]|[\w-]+ *$)/", $tok->val);
            $tok->attrs = array();
            foreach ($attrs as $pair) {
                // Support = and :
                $colon = mb_strpos($pair, ':');
                $equal = mb_strpos($pair, '=');

                // Boolean
                if (false === $colon && false === $equal) {
                    $key = $pair;
                    $val = true;
                } else {
                    $split = false !== $equal ? $equal : $colon;
                    if (false !== $colon && $colon < $equal) {
                        $split = $colon;
                    }
                    $key = mb_substr($pair, 0, $split);
                    $val = mb_substr($pair, ++$split, mb_strlen($pair));
                }
                $tok->attrs[preg_replace("/^['\"]|['\"]$/g", '', $key)] = $val;
            }
            return $tok;
        }

        // Indent
        if (preg_match("/^\n( *)/", $this->input, $matches)) {
            ++$this->lineno;
            $tok = $this->token('indent', $matches);
            $indents = mb_strlen($tok->val) / 2;
            if ("\n" === $this->input[0]) {
                $tok->type = 'newline';
                return $tok;
            } elseif (0 !== $indents % 1) {
                throw new ParserException(sprintf(
                    'Invalid indentation, got %d space%s, must be a multiple or two.',
                    $count = mb_strlen($tok->val), $count > 1 ? 's' : ''
                ));
            } elseif ($indents === $this->lastIndents) {
                $tok->type = 'newline';
            } elseif ($indents > $this->lastIndents + 1) {
                throw new ParserException(sprintf(
                    'Invalid indentation, got %d expected %d.',
                    $indents, ($this->lastIndents + 1)
                ));
            } elseif ($indents < $this->lastIndents) {
                $n = $this->lastIndents - $indents;
                $tok->type = 'outdent';
                while (--$n) {
                    $this->deferredTokens[] = (object) array(
                        'type'  => 'outdent',
                        'line'  => $this->lineno
                    );
                }
            }
            $this->lastIndents = $indents;
            return $tok;
        }

        // Text
        if (preg_match("/^(?:\| ?)?([^\n]+)/", $this->input, $matches)) {
            return $this->token('text', $matches);
        }
    }

    public function parse()
    {
        $buf = array();
        while ('eos' !== $this->peek()->type) {
            $buf[] = $this->parseExpr();
        }
        return implode('', $buf);
    }

    protected function peek()
    {
        return $this->stash = $this->advance();
    }

    protected function expect($type)
    {
        if ($type === $this->peek()->type) {
            return $this->advance();
        } else {
            throw new ParserException(sprintf('Expected "%s", but got "%s"',
                $type, $this->peek()->type
            ));
        }
    }

    protected function parseExpr()
    {
        switch ($this->peek()->type) {
            case 'tag':
                return $this->parseTag();
            case 'doctype':
                return $this->parseDoctype();
            case 'filter':
                return $this->parseFilter();
            case 'text':
                return $this->advance()->val;
            case 'id':
            case 'class':
                $tok = $this->advance();
                $this->deferredTokens[] = (object) array(
                    'type'  => 'tag',
                    'val'   => 'div',
                    'line'  => $this->lineno
                );
                $this->deferredTokens[] = $tok;
                return $this->parseExpr();
            case 'code':
                $tok = $this->advance();
                $val = $tok->val;
                $buf = sprintf($tok->buffer ? '<?php echo %s ?>' : '<?php %s ?>', trim($val));
                                    $buf .= $this->parseBlock();

                return $buf;
            case 'newline':
                $this->advance();
                return $this->parseExpr();
        }
    }

    protected function parseDoctype()
    {
        $name = $this->expect('doctype')->val;
        if ('5' === $name) {
            $this->mode = 'html 5';
        } elseif (!isset($this->doctypes[$name])) {
            throw new ParserException(sprintf('Unknown Doctype: "%s"', $name));
        }
        return $this->doctypes[$name];
    }

    protected function parseFilter()
    {
        $name = $this->expect('filter')->val;
        if (isset($this->filters[$name])) {
            $class = $this->filters[$name];
            return $class::filter($this->parseTextBlock());
        } else {
            throw new ParserException(sprintf('Unknown filter: "%s"', $name));
        }
    }

    protected function parseTextBlock()
    {
        $buf = array();
        $this->expect('indent');
        while ('text' === $this->peek()->type || 'newline' === $this->peek()->type) {
            if ('newline' === $this->peek()->type) {
                $this->advance();
                $buf[] = "\n";
            } else {
                $buf[] = $this->advance()->val;
            }
        }
        $this->expect('outdent');
        return implode('', $buf);
    }

    protected function parseBlock()
    {
        $buf = array();
        $this->expect('indent');
        while ('outdent' !== $this->peek()->type) {
            $buf[] = $this->parseExpr();
        }
        $this->expect('outdent');
        return implode("\n", $buf);
    }

    protected function parseTag()
    {
        $name = $this->advance()->val;
        $html5 = 'html 5' === $this->mode;
        $hasAttrs = false;
        $attrBuf = '';
        $codeClass = '';
        $classes = array();
        $attrs = array();
        $buf = array();

        // (attrs | class | id)*
        while (true) {
            switch ($this->peek()->type) {
                case 'id':
                    $hasAttrs = true;
                    $attrs['id'] = sprintf('"%s"', $this->advance()->val);
                    continue;
                case 'class':
                    $hasAttrs = true;
                    $classes[] = $this->advance()->val;
                    continue;
                case 'attrs':
                    $hasAttrs = true;
                    $obj = $this->advance()->attrs;
                    foreach ($obj as $key => $val) {
                        if ('class' === $key) {
                            $continue = $val;
                        } else {
                            $attrs[$key] = null === $val ? true : $val;
                        }
                    }
                    continue;
                default:
                    break 2;
            }
        }

        // Text?
        if ('text' === $this->peek()->type) {
            $buf[] = $this->advance()->val;
        }

        // (code | block)
        switch ($this->peek()->type) {
            case 'code':
                $tok = $this->advance();
                if ($tok->buffer) {
                    $buf[] = '<?php echo' . $tok->val . ' ?>';
                } else {
                    $buf[] = '<?php' . $tok->val . ' ?>';
                }
                break;
            case 'indent':
                $buf[] = $this->parseBlock();
                break;
        }

        // Build attrs
        if ($hasAttrs) {
            // ATTRIBUTES
        }

        // Build the tag
        if (isset($this->selfClosing[$name])) {
            return '<' . $name . ($html5 ? '' : '/') . '>';
        } else {
            return '<' . $name . '>' . implode("\n", $buf) . '</' . $name . '>';
        }
    }
}
