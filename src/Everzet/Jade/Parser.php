<?php

namespace Everzet\Jade;

use \Everzet\Jade\ParserException;
use \Everzet\Jade\Filters\BaseFilterInterface as Filter;
use \Everzet\Jade\Filters\BlockFilterInterface as BlockFilter;
use \Everzet\Jade\Filters\TextFilterInterface as TextFilter;
use \Everzet\Jade\Filters\PHP;
use \Everzet\Jade\Filters\CDATA;
use \Everzet\Jade\Filters\JavaScript;
use \Everzet\Jade\Filters\CSS;

/*
 * This file is part of the Jade package.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Jade Parser.
 *
 * @package     Jade
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
        "/^ *if *.*\: *$/"        => 'endif',
        "/^ *else *\: *$/"        => 'endif',
        "/^ *else *if *.*\: *$/"  => 'endif',
        "/^ *while *.*\: *$/"     => 'endwhile',
        "/^ *for *.*\: *$/"       => 'endfor',
        "/^ *foreach *.*\: *$/"   => 'endforeach',
        "/^ *switch *.*\: *$/"    => 'endswitch',
        "/^ *case *.* *\: *$/"    => 'break'
    );
    /**
     * Autoreplaceable tags
     *
     * @var     array
     */
    protected $autotags = array(
        'form:post'         => array('tag' => 'form', 'attrs' => array('method' => 'POST')),
        'link:css'          => array('tag' => 'link', 'attrs' => array(
            'rel'   => 'stylesheet',
            'type'  => 'text/css'
        )),
        'script:js'         => array('tag' => 'script', 'attrs' => array(
            'type'  => 'text/javascript'
        )),
        'input:button'      => array('tag' => 'input', 'attrs' => array('type' => 'button')),
        'input:checkbox'    => array('tag' => 'input', 'attrs' => array('type' => 'checkbox')),
        'input:file'        => array('tag' => 'input', 'attrs' => array('type' => 'file')),
        'input:hidden'      => array('tag' => 'input', 'attrs' => array('type' => 'hidden')),
        'input:image'       => array('tag' => 'input', 'attrs' => array('type' => 'image')),
        'input:password'    => array('tag' => 'input', 'attrs' => array('type' => 'password')),
        'input:radio'       => array('tag' => 'input', 'attrs' => array('type' => 'radio')),
        'input:reset'       => array('tag' => 'input', 'attrs' => array('type' => 'reset')),
        'input:submit'      => array('tag' => 'input', 'attrs' => array('type' => 'submit')),
        'input:text'        => array('tag' => 'input', 'attrs' => array('type' => 'text')),
        'input:search'      => array('tag' => 'input', 'attrs' => array('type' => 'search')),
        'input:tel'         => array('tag' => 'input', 'attrs' => array('type' => 'tel')),
        'input:url'         => array('tag' => 'input', 'attrs' => array('type' => 'url')),
        'input:email'       => array('tag' => 'input', 'attrs' => array('type' => 'email')),
        'input:datetime'    => array('tag' => 'input', 'attrs' => array('type' => 'datetime')),
        'input:date'        => array('tag' => 'input', 'attrs' => array('type' => 'date')),
        'input:month'       => array('tag' => 'input', 'attrs' => array('type' => 'month')),
        'input:week'        => array('tag' => 'input', 'attrs' => array('type' => 'week')),
        'input:time'        => array('tag' => 'input', 'attrs' => array('type' => 'time')),
        'input:number'      => array('tag' => 'input', 'attrs' => array('type' => 'number')),
        'input:range'       => array('tag' => 'input', 'attrs' => array('type' => 'range')),
        'input:color'       => array('tag' => 'input', 'attrs' => array('type' => 'color')),
        'input:datetime-local' => array('tag' => 'input', 'attrs' => array(
            'type'  => 'datetime-local'
        ))
    );

    protected $input;
    protected $deferredTokens = array();
    protected $lastIndents = 0;
    protected $lineno = 1;
    protected $stash;
    protected $mode;

    /**
     * Inits Jade parser with the given input string.
     *
     * @param   string  $input  Jade string
     */
    public function __construct($input = null)
    {
        if (null !== $input) {
            $this->setInput($input);
        }

        // Set basic filters
        $this->setFilter('php', new PHP());
        $this->setFilter('cdata', new CDATA());
        $this->setFilter('javascript', new JavaScript());
        $this->setFilter('style', new CSS());
    }

    /**
     * Sets parser mode
     *
     * @param   string  $mode
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    /**
     * Define filter class by filter's name.
     *
     * @param   string  $name   filter name
     * @param   Filter  $class  filter object
     */
    public function setFilter($name, Filter $class)
    {
        $this->filters[$name] = $class;
    }

    /**
     * Sets custom Code block ending.
     *
     * @param   string  $begin  code block begin regexp
     * @param   string  $end    code block end ('endif' for example)
     */
    public function setBlockEnd($begin, $end)
    {
        $this->blocks[$begin] = $end;
    }

    /**
     * Sets custom autotag for further autoreplaces
     *
     * @param   string  $holder     tag holder (input:text for example)
     * @param   string  $tagName    output tag name (input)
     * @param   array   $attrs      output tag attributes (array('type' => 'text'))
     */
    public function setAutotag($holder, $tagName, array $attrs)
    {
        $this->autotags[$holder] = array('tag' => $tagName, 'attrs' => $attrs);
    }

    /**
     * Sets Jade string to parse
     *
     * @param   string  $input  Jade to parse
     */
    public function setInput($input)
    {
        $this->input = preg_replace("/\r\n|\r/", "\n", $input);
    }

    /**
     * Parse input string.
     *
     * @param   string  $input  Jade string
     *
     * @return  string          HTML
     */
    public function parse($input = null)
    {
        if (function_exists('mb_internal_encoding')) {
            $mbEncoding = mb_internal_encoding();
            mb_internal_encoding('UTF-8');
        }

        if (null !== $input) {
            $this->setInput($input);
        }
        $this->deferredTokens = array();
        $this->lastIndents = 0;
        $this->lineno = 1;
        $this->stash = null;
        $this->mode = null;

        $buf = array();
        while ('eos' !== $this->peek()->type) {
            $buf[] = $this->parseExpr();
        }
        $html = implode('', $buf);
        $html = preg_replace(array("/^\n/", "/\n$/", "/^ */", "/ *$/"), '', $html);

        if (isset($mbEncoding)) {
            mb_internal_encoding($mbEncoding);
        }

        return $html;
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

        // Starting attributes RegEx
        $attrRegex  = "/^\( *((?:";
        // Paired brackets
        $attrRegex .= "(?:\([^\)]*\))|";
        // Brackets inside double quotes
        $attrRegex .= "(?:\"[^\"]*\")|";
        // Brackets inside single quotes
        $attrRegex .= "(?:'[^']*')|";
        // Not brackets
        $attrRegex .= "[^\)]";
        // Ending attributes RegEx
        $attrRegex .= ")+) *\)/";

        // Attributes
        if (preg_match($attrRegex, $this->input, $matches)) {
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
                    $val = preg_replace("/^\s*(?:['\"])?|(?:['\"])?\s*$/", '', 
                        mb_substr($pair, ++$split, mb_strlen($pair))
                    );
                }
                $tok->attrs[preg_replace("/^ +| +$|^['\"]|['\"]$/", '', $key)] = $val;
            }
            return $tok;
        }

        // Jade comment
        if (preg_match("/^(?:\n)? *\/\/([^\n]*)/", $this->input, $matches)) {
            return $this->token('newline', $matches);
        }

        // Indent
        if (preg_match("/^\n( *)/", $this->input, $matches)) {
            ++$this->lineno;
            $tok = $this->token('indent', $matches);
            $indents = mb_strlen($tok->val) / 2;
            if (mb_strlen($this->input) && "\n" === $this->input[0]) {
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

        // HTML comment
        if (preg_match("/^\/([^\n]*)/", $this->input, $matches)) {
            return $this->token('comment', $matches);
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
     * @throws  \Everzet\Jade\ParserException
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
                return $this->filterText($this->advance()->val) . "\n";
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
                $buf = $tok->buffer ? '<?php echo ' : '<?php ';

                $beg = preg_replace(array("/^ */", "/ *$/"), '', $val);
                $end = null;
                $indents = $this->getIndentation();
                foreach ($this->blocks as $regexp => $close) {
                    if (preg_match($regexp, $beg)) {
                        $end = $close;
                        break;
                    }
                }
                $buf .= $beg;
                $this->skipNewlines();
                if ('indent' === $this->peek()->type) {
                    $buf .= (null === $end ? '{' : '') . " ?>\n";
                    $buf .= $this->parseBlock();
                    $buf  = preg_replace(array("/^ */", "/ *$/"), '', $buf);
                    $peek = $this->peek();
                    if ('code' !== $peek->type || false === strpos($peek->val, 'else')) {
                        $buf .= sprintf("%s<?php %s ?>\n",
                            $indents,
                            null === $end ? '}' : $end . ';'
                        );
                    }
                } else {
                    $buf .= " ?>\n";
                }

                return $buf;
            case 'comment':
                $tok = $this->advance();
                $val = preg_replace(array("/^ */", "/ *$/"), '', $tok->val);
                $indents = $this->getIndentation();
                if (preg_match("/^\[if[^\]]+\]$/", $val)) {
                    $beg = sprintf('<!--%s>', $val);
                    $end = '<![endif]-->';
                    $val = '';
                } else {
                    $beg = "<!--";
                    $end = "-->";
                }
                if ('indent' === $this->peek()->type) {
                    $buf = $beg . "\n";
                    if ('' !== $val) {
                        $buf .= $indents . $val . "\n";
                    }
                    $buf .= $this->parseBlock();
                    $buf .= $indents . $end;
                } else {
                    $buf = sprintf("<!-- %s -->", $val);
                }
                return $buf . "\n";
            case 'newline':
                $this->advance();
                return $this->parseExpr();
        }
    }

    /**
     * Skipping newlines
     *
     */
    protected function skipNewlines()
    {
        while ('newline' === $this->peek()->type) {
            $this->advance();
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
     * Filter text with all filters, that implements TextFilter interface
     *
     * @param   string  $text   text to filter
     * 
     * @return  string          filtered text
     */
    protected function filterText($text)
    {
        foreach ($this->filters as $filter) {
            if ($filter instanceof TextFilter) {
                $text = $filter->filterText($text);
            }
        }

        return $text;
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
            $filter = $this->filters[$name];
            if ($filter instanceof BlockFilter) {
                return $filter->filter($this->parseTextBlock(), $this->lastIndents);
            } else {
                throw new ParserException(
                    sprintf('Filter: "%s" must implements BlockFilterInterface', $name)
                );
            }
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
                $buf[] = $this->filterText($this->advance()->val);
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
            $buf[] = $this->getIndentation() . $this->filterText(
                preg_replace("/^ */", '', $this->parseExpr())
            );
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

        // autoreplace tag definition with autotag
        if (array_key_exists($name, $this->autotags)) {
            $autotag = $this->autotags[$name];
            $name = $autotag['tag'];

            if (isset($autotag['attrs']) && count($autotag['attrs'])) {
                $hasAttrs = true;
                $attrs = array_merge($attrs, $autotag['attrs']);
            }
        }

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
            $val = preg_replace(array("/^ */", "/ *$/"), '', $this->advance()->val);
            if ('' !== $val) {
                $buf[] = $indents . '  ' . $this->filterText($val);
            }
        }

        // Code?
        if ('code' === $this->peek()->type) {
            $tok = $this->advance();
            if ($tok->buffer) {
                $buf[] = '<?php echo' . $tok->val . ' ?>';
            } else {
                $buf[] = '<?php' . $tok->val . ' ?>';
            }
        }

        // Skip newlines
        $this->skipNewlines();

        // Block?
        if ('indent' === $this->peek()->type) {
            $buf[] = $this->parseBlock();
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
                if (true === $value || 'true' === $value) {
                    $attributes[] = $html5 ? $key : sprintf('%s="%s"', $key, $key);
                } elseif (false !== $value && 'false' !== $value && 'null' !== $value && '' !== $value) {
                    $attributes[] = sprintf('%s="%s"', $key,
                        $this->filterText(htmlentities($value, ENT_COMPAT, 'UTF-8'))
                    );
                }
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
                    preg_replace(array("/^ */", "/ *$/"), '', $buf) . '</' . $name . '>';
            } else {
                return '<' . $name . $attrBuf . ">\n" . preg_replace("/\n *$/", "\n", $buf) . $indents . '</' . $name . '>';
            }
        }
    }
}
