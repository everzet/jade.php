<?php

namespace Everzet\HTAML\Renderers;

use \Everzet\HTAML\Parser;
use \Everzet\HTAML\ParserException;

/*
 * This file is part of the HTAML package.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * HTAML basic renderer
 *
 * @package     HTAML
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class Renderer
{
    protected $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function renderHTAMLTo($htamlFile, $htmlFile)
    {
        file_put_contents($htmlFile, $this->parser->parse(file_get_contents($htamlFile)));
    }
}
