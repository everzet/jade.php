<?php

require_once __DIR__.'/UniversalClassLoader.php';
use Symfony\Framework\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->registerNamespaces(array(
    'Everzet' => __DIR__.'/src'
));
$loader->register();

$parser = new \Everzet\HTAML\Parser(<<<HTAML
!!! 5
html
  asd
    - switch
      - case 2
        asd
      - case 3
        asd
    - vd
      as
HTAML
);

var_dump($parser->parse());
