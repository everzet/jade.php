<?php

namespace Everzet\HTAML\Filters;

use \Everzet\HTAML\Filters\BlockFilterInterface;
use \Everzet\HTAML\Filters\TextFilterInterface;

/*
 * This file is part of the HTAML package.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * PHP filter.
 *
 * @package     HTAML
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class PHP implements BlockFilterInterface, TextFilterInterface
{
    public function filterText($str)
    {
        return preg_replace_callback("/{{((?!}}).*)}}/", function($matches) {
            return sprintf('<?php echo %s ?>', html_entity_decode($matches[1]));
        }, $str);
    }

    public function filter($str, $indentation = 0)
    {
        $php = <<<PHP
<?php
$str
?>
PHP;
        return preg_replace("/\n/", "\n" . str_repeat('  ', $indentation), $php);
    }
}
