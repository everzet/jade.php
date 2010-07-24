<?php

namespace Everzet\HTAML;

use \Everzet\HTAML\ParserException;
use \Everzet\HTAML\Filters\Filter;
use \Everzet\HTAML\Filters\PHP;
use \Everzet\HTAML\Filters\CDATA;
use \Everzet\HTAML\Filters\JavaScript;

/*
 * This file is part of the HTAML package.
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
    /**
     * Tags to self-close (<hr "/">).
     *
     * @var     array
     */
    protected $selfClosing = array(
        'meta', 'img', 'link', 'br', 'hr', 'input', 'area', 'base'
    );
    /**
     * Doctypes.
     *
     * @var     array
     */
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
    /**
     * Available filters.
     *
     * @var     array
     */
    protected $filters = array();
    /**
     * Block definitions (begin => end: if => endif, for => endfor...).
     *
     * @var     array
     */
    protected $blocks = array(
        'if'        => 'endif',
        'else'      => 'endif',
        'elseif'    => 'endif',
        'while'     => 'endwhile',
        'for'       => 'endfor',
        'foreach'   => 'endforeach',
        'switch'    => 'endswitch',
        'case'      => 'break'
    );

    protected $input;
    protected $deferredTokens = array();
    protected $lastIndents = 0;
    protected $lineno = 1;
    protected $stash;
    protected $mode;

    /**
     * Inits HTAML parser with the given input string.
     *
     * @param   string  $str    HTAML string
     */
    public function __construct($str)
    {
        $this->input = preg_replace("/\r\n|\r/", "\n", $str);
        $this->deferredTokens = array();
        $this->lastIndents = 0;
        $this->lineno = 1;

        // Set basic filters
        $this->setFilter('php', new PHP());
        $this->setFilter('cdata', new CDATA());
        $this->setFilter('javascript', new JavaScript());
    }

    /**
     * Define filter class by filter's name.
     *
     * @param   string  $name   filter name
     * @param   Filter  $class  filter object
     */
    public function setFilter($name, Filter $class) {
        $this->filters[$name] = $class;
    }

    /**
     * Sets custom Code block ending.
     *
     * @param   string  $begin  code block begin ('if' for example)
     * @param   string  $end    code block end ('endif' for example)
     */
    public function setBlockEnd($begin, $end) {
        $this->blocks[$begin] = $end;
    }

    /**
     * Parse input string.
     *
     * @return  string          HTML
     */
    public function parse()
    {
        $buf = array();
        while ('eos' !== $this->peek()->type) {
            $buf[] = $this->parseExpr();
        }
        $html = implode('', $buf);

        return preg_replace(array("/^\n/", "/\n$/", "/^( *)/", "/( *)$/"), '', $html);
    }

    /**
     * Generate token object.
     * 
     * @param   string  $type       token type
     * @param   array   $matches    regex matches
     * 
     * @return  stdClass
     */
    protected function token($type, array $matches = array('', ''))
    {
        $this->input = mb_substr($this->input, mb_strlen($matches[0]));
        return (object) array(
            'type'  => $type,
            'line'  => $this->lineno,
            'val'   => isset($matches[1]) ? $matches[1] : null
        );
    }

    /**
     * Returns the next token object.
     *
     * @return  stdClass
     */
    protected function advance()
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
                    // Split on first = or :
                    $split = false !== $equal ? $equal : $colon;
                    if (false !== $colon && $colon < $equal) {
                        $split = $colon;
                    }
                    $key = mb_substr($pair, 0, $split);
                    $val = preg_replace("/^ +| +$|^['\"]|['\"]$/", '', 
                        mb_substr($pair, ++$split, mb_strlen($pair))
                    );
                    $val = $this->filters['php']->replaceHoldersWithEcho($val);
                }
                $tok->attrs[preg_replace("/^ +| +$|^['\"]|['\"]$/", '', $key)] = $val;
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

    /**
     * Single token lookahead.
     *
     * @return  stdClass
     */
    protected function peek()
    {
        return $this->stash = $this->advance();
    }

    /**
     * Expect the given type, or throw an exception.
     *
     * @param   string  $type   token type
     * 
     * @return  stdClass
     * 
     * @throws  \Everzet\HTAML\ParserException
     */
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

    /**
     * Parse current token expression.
     *
     * @return  string
     */
    protected function parseExpr()
    {
        switch ($this->peek()->type) {
            case 'tag':
                return $this->parseTag() . "\n";
            case 'doctype':
                return $this->parseDoctype() . "\n";
            case 'filter':
                return $this->parseFilter() . "\n";
            case 'text':
                return $this->filters['php']->replaceHoldersWithEcho($this->advance()->val) . "\n";
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
                if ($tok->buffer) {
                    $buf = sprintf('<?php echo %s ?>',
                        preg_replace(array("/^( *)/", "/( *)$/"), '', $val)
                    );
                } else {
                    $beg = preg_replace(array("/^( *)/", "/( *)$/"), '', $val);
                    $end = null;
                    foreach ($this->blocks as $open => $close) {
                        if (false !== mb_strpos($beg, $open)) {
                            $end = $close;
                        }
                    }
                    $buf = sprintf('<?php %s', $beg);
                    if ('indent' === $this->peek()->type) {
                        $buf .= (null === $end ? '{' : ':') . " ?>\n";
                        $buf .= $this->parseBlock();

                        $peek = $this->peek();
                        if ('code' !== $peek->type || false === strpos($peek->val, 'else')) {
                            $buf .= sprintf("%s<?php %s ?>\n",
                                $this->getIndentation(),
                                null === $end ? '}' : $end . ';'
                            );
                        }
                    } else {
                        $buf .= " ?>\n";
                    }
                }
                return $buf;
            case 'newline':
                $this->advance();
                return $this->parseExpr();
        }
    }

    /**
     * Calculates & generates indentation string.
     *
     * @param   integer $plus   adding indentation
     * 
     * @return  string          left spaces
     */
    protected function getIndentation($plus = 0)
    {
        return str_repeat('  ', $this->lastIndents + intval($plus));
    }

    /**
     * Parse Doctype.
     *
     * @return  string
     */
    protected function parseDoctype()
    {
        $name = $this->expect('doctype')->val;
        if ('5' === $name) {
            $this->mode = 'html 5';
        } elseif (null === $name) {
            $name = 'default';
        } elseif (!isset($this->doctypes[$name])) {
            throw new ParserException(sprintf('Unknown Doctype: "%s"', $name));
        }
        return $this->doctypes[$name];
    }

    /**
     * Parse & execute filter.
     *
     * @return  string
     */
    protected function parseFilter()
    {
        $name = $this->expect('filter')->val;
        if (isset($this->filters[$name])) {
            return $this->filters[$name]->filter($this->parseTextBlock(), $this->lastIndents);
        } else {
            throw new ParserException(sprintf('Unknown filter: "%s"', $name));
        }
    }

    /**
     * Parse text block.
     *
     * @return  string
     */
    protected function parseTextBlock()
    {
        $buf = array();
        $this->expect('indent');
        while ('text' === $this->peek()->type || 'newline' === $this->peek()->type) {
            if ('newline' === $this->peek()->type) {
                $this->advance();
            } else {
                $buf[] = $this->filters['php']->replaceHoldersWithEcho($this->advance()->val);
            }
        }
        $this->expect('outdent');
        return implode("\n", $buf);
    }

    /**
     * Parse indented block.
     *
     * @return  string
     */
    protected function parseBlock()
    {
        $buf = array();
        $this->expect('indent');
        while ('outdent' !== $this->peek()->type) {
            $buf[] = $this->getIndentation() .
                $this->filters['php']->replaceHoldersWithEcho($this->parseExpr());
        }
        $this->expect('outdent');
        return implode('', $buf);
    }

    /**
     * Parse tag.
     *
     * @return  string
     */
    protected function parseTag()
    {
        $name = $this->advance()->val;
        $html5 = 'html 5' === $this->mode;
        $hasAttrs = false;
        $attrBuf = '';
        $codeClass = null;
        $classes = array();
        $attrs = array();
        $buf = array();
        $indents = $this->getIndentation();

        // (attrs | class | id)*
        while (true) {
            switch ($this->peek()->type) {
                case 'id':
                    $hasAttrs = true;
                    $attrs['id'] = $this->advance()->val;
                    continue;
                case 'class':
                    $hasAttrs = true;
                    $classes[] = $this->advance()->val;
                    continue;
                case 'attrs':
                    $hasAttrs = true;
                    foreach ($this->advance()->attrs as $key => $val) {
                        if ('class' === $key) {
                            $codeClass = $val;
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
            $buf[] = $indents . '  ' .
                preg_replace(array("/^( *)/", "/( *)$/"), '', $this->advance()->val);
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
            if (count($classes)) {
                $attrs['class'] = implode(' ', $classes);
            }
            if (null !== $codeClass) {
                if (isset($attrs['class'])) {
                    $attrs['class'] .= ' ' . $codeClass;
                } else {
                    $attrs['class'] = $codeClass;
                }
            }

            // Attributes
            $attributes = array();
            foreach ($attrs as $key => $value) {
                $attributes[] = sprintf('%s="%s"', $key, true === $value ? $key : $value);
            }
            if (count($attributes)) {
                $attrBuf .= ' ' . implode(' ', $attributes);
            }
        }

        // Build the tag
        if (in_array($name, $this->selfClosing)) {
            return $indents . '<' . $name . $attrBuf . ($html5 ? '' : ' /') . '>';
        } else {
            $buf = implode("\n", $buf);

            if (false === mb_strpos($buf, "\n")) {
                return '<' . $name . $attrBuf . '>' .
                    preg_replace(array("/^( *)/", "/( *)$/"), '', $buf) . '</' . $name . '>';
            } else {
                return '<' . $name . $attrBuf . ">\n" . $buf . $indents . '</' . $name . '>';
            }
        }
    }
}
