<?php
/*PhpDoc:
name: api.php
title: script d'appel de l'export DCAT et de son contexte
doc: |
  Ce script est appelé lors de l'appel de https://dido.geoapi.fr/v1/xxx
  ou de http://localhost/geoapi/dido/api.php/v1/xxx
journal: |
  8/7/2021:
    - chgt retour de catalog()
  6-7/7/2021:
    - améliorations
    - manque la pagination de l'export
  5/7/2021:
    - plus de violations dans le validataeur EU mais des warnings
  4/7/2021:
    - version non paginée
  1/7/2021:
    - création d'un fantome
includes:
  - themesdido.inc.php
  - geozones.inc.php
  - frequency.inc.php
  - catalog.inc.php
  - ../../phplib/pgsql.inc.php
*/
require __DIR__.'/themesdido.inc.php';
require __DIR__.'/geozones.inc.php';
require __DIR__.'/frequency.inc.php';
require __DIR__.'/catalog.inc.php';
require __DIR__.'/../../phplib/pgsql.inc.php';

// retourne un ensemble d'objets dont l'objet Catalog et des déclarations initiales
function headers(array $datasetUris) {
  return array_merge(
    ThemeDido::jsonld(), // Déclaration des thèmes DiDo et du Scheme
    GeoZone::jsonld(), // Déclaration des GéoZones
    Frequency::jsonld(), // Déclaration des frequences
    [catalog($datasetUris)] // Déclaration du catalogue
  );
}

// dcatcontext.jsonld
if (in_array($_SERVER['REQUEST_URI'], ['/geoapi/dido/api.php/v1/dcatcontext.jsonld', '/v1/dcatcontext.jsonld'])) {
  header('Content-type: application/ld+json; charset="utf-8"');
  $context = file_get_contents(__DIR__.'/dcatcontext.json');
  echo "$context\n"; die();
  $context = json_decode($context, true);
  die(json_encode($context, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
}

// ! dcatexport.jsonld
elseif (!in_array($_SERVER['REQUEST_URI'], ['/geoapi/dido/api.php/v1/dcatexport.jsonld','/v1/dcatexport.jsonld'])) {
  header("HTTP/1.0 404 Not Found");
  header('Content-type: text/plain; charset="utf-8"');
  die("No match for '$_SERVER[REQUEST_URI]'\n");
}

// dcatexport.jsonld
else {
  //echo "<pre>";
  if (($_SERVER['SERVER_NAME']=='localhost')) // en localhost sur le Mac
    PgSql::open('pgsql://docker@pgsqlserver/gis/public');
  else // sur le serveur dido.geoapi.fr
    PgSql::open('pgsql://benoit@db207552-001.dbaas.ovh.net:35250/datagouv/public');
  
  $graph = [];
  
  // Lecture des ressources stockées en base et fabrication de la liste des URI des datasets DCAT
  $datasetUris = [];
  foreach (PgSql::query("select dcat from didodcat") as $tuple) {
    //echo "$tuple[dcat]\n";
    $resource = json_decode($tuple['dcat'], true);
    $graph[] = $resource;
    if (isset($resource['@type'])
        && ((is_string($resource['@type']) && ($resource['@type']=='Dataset'))
             || (is_array($resource['@type']) && in_array('Dataset', $resource['@type'])))) {
      $datasetUris[] = $resource['@id'];
    }
  }  
  
  // Génération de l'export du catalogue DCAT non paginé
  header('Content-type: application/ld+json; charset="utf-8"');
  $json = json_encode(
    [
      '@context'=> 'https://dido.geoapi.fr/v1/dcatcontext.jsonld',
      '@graph'=> array_merge(headers($datasetUris), $graph),
    ],
    JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE
  );

  // En localhost je remplace les URL par des URL locales pour faciliter les tests en local
  if (($_SERVER['SERVER_NAME']=='localhost')) // en localhost sur le Mac
    die(
      str_replace(
        [
          'https://dido.geoapi.fr/v1',
          'https://dido.geoapi.fr/id',
        ],
        [
          'http://localhost/geoapi/dido/api.php/v1',
          'http://localhost/geoapi/dido/id.php',
        ],
        $json
      )
    );
  else // sur le serveur dido.geoapi.fr
    die($json);
}
