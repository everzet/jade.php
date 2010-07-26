<?php

namespace Everzet\HTAML\Renderers;

use \Everzet\HTAML\Parser;
use \Everzet\HTAML\Renderers\Renderer;

/*
 * This file is part of the HTAML package.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * HTAML cached renderer
 *
 * @package     HTAML
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class Cached extends Renderer
{
    protected $path;
    protected $extension;

    public function __construct(Parser $parser, $path, $extension = 'php')
    {
        $this->path = $path;
        $this->extension = $extension;

        parent::__construct($parser);
    }

    public function compileTemplate($htamlFile, $filename = null)
    {
        if (null === $filename) {
            $filename = md5($htamlFile);
        }

        $htmlFile = sprintf('%s/%s.%s', $this->path, $filename, $this->extension);

        if (!file_exists($htmlFile) || filemtime($htamlFile) > filemtime($htmlFile)) {
            return $this->compile($htamlFile, $htmlFile);
        } else {
            return $htmlFile;
        }
    }
}
