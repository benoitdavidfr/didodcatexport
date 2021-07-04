<?php
/*PhpDoc:
name: api.php
title: script appelé lors de l'appel de l'API export DCAT
doc: |
  Ce script est appelé lors de l'appel de https://dido.geoapi.fr/v1/dcatexport.jsonld
  ou de http://localhost/geoapi/dido/api.php/v1/dcatexport.jsonld
journal: |
  4/7/2021:
    - version non paginée
  1/7/2021:
    - création d'un fantome
*/
require __DIR__.'/../../phplib/pgsql.inc.php';

// génère l'objet Catalog
function catalog(array $datasetIds) {
  return [
    '@id'=> 'https://dido.geoapi.fr/id/catalog',
    '@type'=> 'Catalog',
    'title'=> "Catalogue DiDo",
    'dataset'=> $datasetIds,
    'homepage'=> 'https://dido.geoapi.fr/',
    'language'=> 'http://publications.europa.eu/resource/authority/language/FRA',
    'publisher'=> [
      '@id'=> 'https://dido.geoapi.fr/id/organizations/SDES',
      '@type'=> 'Organization',
      'name'=> "Ministère de la transition écologique (MTE), Service des Données et des Etudes Statistiques",
      'nick'=> 'SDES',
      'comment'=> "Le SDES est le service statistique du ministère de la transition écologique. Il fait partie du Commissariat Général au Développement Durable (CGDD)",
    ],
  ];
}

if (in_array($_SERVER['REQUEST_URI'], ['/geoapi/dido/api.php/v1/dcatexport.jsonld','/v1/dcatexport.jsonld'])) {
  //echo "<pre>";
  if (($_SERVER['SERVER_NAME']=='localhost')) // en localhost sur le Mac
    PgSql::open('pgsql://docker@pgsqlserver/gis/public');
  else // sur le serveur dido.geoapi.fr
    PgSql::open('pgsql://benoit@db207552-001.dbaas.ovh.net:35250/datagouv/public');
  $graph = [];
  $datasetIds = [];
  foreach (PgSql::query("select dcat from didodcat") as $tuple) {
    //echo "$tuple[dcat]\n";
    $resource = json_decode($tuple['dcat'], true);
    $graph[] = $resource;
    if (isset($resource['@type'])
        && ((is_string($resource['@type']) && ($resource['@type']=='Dataset'))
             || (is_array($resource['@type']) && in_array('Dataset', $resource['@type'])))) {
      $datasetIds[] = $resource['@id'];
    }
  }
  
  $graph[] = catalog($datasetIds);
  
  header('Content-type: application/json; charset="utf-8"');
  die(json_encode([
    '@context'=> 'https://dido.geoapi.fr/v1/dcatcontext.jsonld',
    '@graph'=> $graph,
  ]));
}
else {
  header("HTTP/1.0 404 Not Found");
  header('Content-type: text/plain; charset="utf-8"');
  die("No match for '$_SERVER[REQUEST_URI]'\n");
}
