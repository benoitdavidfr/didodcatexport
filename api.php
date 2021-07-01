<?php
/*PhpDoc:
name: api.php
title: script appelé lors de l'appel de l'API export DCAT
doc: |
  Ce script est appelé lors de l'appel de https://dido.geoapi.fr/v1/dcatexport.jsonld
  ou de http://localhost/geoapi/dido/api.php/v1/dcatexport.jsonld
journal: |
  1/7/2021:
    - création d'un fantome
*/
echo "<h2>api.php</h2><pre>\n";
//print_r($_SERVER);
echo "REQUEST_URI -> $_SERVER[REQUEST_URI]<br>\n";
