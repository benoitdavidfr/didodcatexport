<?php
/*PhpDoc:
name: id.php
title: script appelé lors de l'appel des URI pour l'export DCAT
doc: |
  Ce script est appelé lors de l'appel des URI
    - https://dido.geoapi.fr/id/catalog pour le catalogue
    - https://dido.geoapi.fr/id/datasets/{id} pour les jeux de données DiDo
    - https://dido.geoapi.fr/id/attachments/{rid} pour les fichiers annexes
    - https://dido.geoapi.fr/id/datafiles/{rid} pour les fichiers de données
    - https://dido.geoapi.fr/id/millesimes/{rid}/{m} pour les millésimes
    - https://dido.geoapi.fr/id/organizations/{id} pour les organisations
    - https://dido.geoapi.fr/id/themes/{id} pour les thèmes DiDo
  Il peut être appelé pour des tests locaux à l'adresse:
    - https://localhost/geoapi/dido/id.php/catalog pour le catalogue
    - http://localhost/geoapi/dido/id.php/datasets/{id} pour les jeux de données DiDo
  Exemples:
    - https://dido.geoapi.fr/id/organizations/60abeb7b17967d0023c883a2
    - http://localhost/geoapi/dido/id.php/organizations/60abeb7b17967d0023c883a2
journal: |
  1/7/2021:
    - première version assez complète
*/
require __DIR__.'/../../phplib/pgsql.inc.php';

$pattern = '!^(/geoapi/dido/id.php/|/id/)(catalog|(datasets|attachments|datafiles|millesimes|organizations|themes)/[^/]+(/[^/]+)?)$!';
if (!preg_match($pattern, $_SERVER['REQUEST_URI'], $matches)) {
  header("HTTP/1.0 404 Not Found");
  header('Content-type: text/plain; charset="utf-8"');
  die("No match for '$_SERVER[REQUEST_URI]'\n");
}

//echo "<h2>id.php</h2><pre>\n";
//print_r($_SERVER); die();
//echo "REQUEST_URI -> $_SERVER[REQUEST_URI]<br>\n";
//print_r($matches);

if ($matches[2] == 'catalog') {
  header('Content-type: text/plain; charset="utf-8"');
  echo "Génération du catalogue<br>\n";
}

else {
  $uri = 'https://dido.geoapi.fr/id/'.$matches[2];
  //echo "URI = $uri\n";
  if (($_SERVER['SERVER_NAME']=='localhost')) // en localhost sur le Mac
    PgSql::open('pgsql://docker@pgsqlserver/gis/public');
  else // sur le serveur dido.geoapi.fr
    PgSql::open('pgsql://benoit@db207552-001.dbaas.ovh.net:35250/datagouv/public');
  $tuples = PgSql::getTuples("select dcat from didodcat where uri='$uri'");
  if (count($tuples) > 0) {
    header('Content-type: application/json; charset="utf-8"');
    echo $tuples[0]['dcat'];
  }
  else {
    header("HTTP/1.0 404 Not Found");
    header('Content-type: text/plain; charset="utf-8"');
    echo "Erreur, URI $uri absente\n";
  }
}
