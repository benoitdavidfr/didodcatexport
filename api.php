<?php
/*PhpDoc:
name: api.php
title: script appelé lors de l'appel de l'API export DCAT
doc: |
  Ce script est appelé lors de l'appel de https://dido.geoapi.fr/v1/xxx
  ou de http://localhost/geoapi/dido/api.php/v1/xxx
journal: |
  4/7/2021:
    - version non paginée
  1/7/2021:
    - création d'un fantome
*/
require __DIR__.'/themesdido.inc.php';
require __DIR__.'/geozones.inc.php';
require __DIR__.'/../../phplib/pgsql.inc.php';

// retourne un ensemble d'objets dont l'objet Catalog et des objets généraux à initialiser
function catalog(array $datasetIds) {
  return array_merge(
    ThemeDido::jsonld(), // Déclaration des thèmes DiDo et du Scheme
    GeoZone::jsonld(), // La traduction des GéoZones
    [
      [
        '@id'=> 'http://publications.europa.eu/resource/authority/language/FRA',
        '@type'=> 'dct:LinguisticSystem',
      ],
      [
        '@id'=> 'https://dido.geoapi.fr/id/catalog',
        '@type'=> 'Catalog',
        'title'=> "Catalogue DiDo",
        'description'=> "Test d'export en DCAT-AP du catalogue DiDo provenant du site école ; l'export est formatté en JSON-LD",
        'dataset'=> $datasetIds,
        'homepage'=> [
          '@id'=> 'https://dido.geoapi.fr/',
          '@type'=> 'foaf:Document',
        ],
        'language'=> 'http://publications.europa.eu/resource/authority/language/FRA',
        'publisher'=> [
          '@id'=> 'https://dido.geoapi.fr/id/organizations/SDES',
          '@type'=> 'Organization',
          'name'=> "Ministère de la transition écologique (MTE), Service des Données et des Etudes Statistiques",
          'nick'=> 'SDES',
          'comment'=> "Le SDES est le service statistique du ministère de la transition écologique. Il fait partie du Commissariat Général au Développement Durable (CGDD)",
        ],
        'themeTaxonomy'=> [
          'http://publications.europa.eu/resource/authority/data-theme',
          'https://dido.geoapi.fr/id/themes',
        ],
      ],
    ]
  );
}

if (in_array($_SERVER['REQUEST_URI'], ['/geoapi/dido/api.php/v1/dcatcontext.jsonld', '/v1/dcatcontext.jsonld'])) {
  header('Content-type: application/ld+json; charset="utf-8"');
  $context = file_get_contents(__DIR__.'/dcatcontext.json');
  echo "$context\n"; die();
  $context = json_decode($context, true);
  die(json_encode($context, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
}

elseif (!in_array($_SERVER['REQUEST_URI'], ['/geoapi/dido/api.php/v1/dcatexport.jsonld','/v1/dcatexport.jsonld'])) {
  header("HTTP/1.0 404 Not Found");
  header('Content-type: text/plain; charset="utf-8"');
  die("No match for '$_SERVER[REQUEST_URI]'\n");
}

else {
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
  
  $graph[] = array_merge(catalog($datasetIds), $graph);
  
  
  header('Content-type: application/ld+json; charset="utf-8"');
  die(json_encode(
    [
      '@context'=> 'https://dido.geoapi.fr/v1/dcatcontext.jsonld',
      '@graph'=> $graph,
    ],
    JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE
  ));
}
