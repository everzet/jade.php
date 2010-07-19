<?php

namespace Everzet\HTAML\Tokens;

use \Everzet\HTAML\Tokens\Token;

class Attrs extends Token
{
    protected $attrs = array();

    public function setAttr($key, $value)
    {
        $this->attrs[$key] = $value;
    }

    public static function regexp()
    {
        return "/^\( *(.+) *\)/";
    }

    public static function create($line, array $matches)
    {
        $tok = new self($line, $matches[1]);
        $attrs = preg_split("/ *, *(?=[\w-]+ *[:=]|[\w-]+ *$)/", $tok->getValue());
        foreach ($attrs as $pair) {
            // Support : and =
            $colon = mb_strpos($pair, ':');
            $equal = mb_strpos($pair, '=');

            // Boolean
            if (false === $colon && false === $equal) {
                $key = $pair;
                $val = true;
            } else {
                // key:value OR key=value
                $split = false !== $equal ? $equal : $colon;
                if (false !== $colon && $colon < $equal) {
                    $split = $colon;
                }
                $key = mb_substr($pair, 0, $split);
                $val = mb_substr(++$split, mb_strlen($pair));
            }

            $tok->setAttr(preg_replace("/^['\"]|['\"]$/g", '', $key), $val);
        }

        return $tok;
    }
}
