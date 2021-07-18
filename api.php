<?php
/*PhpDoc:
name: api.php
title: api.php - script d'appel de l'export DCAT et de son contexte
doc: |
  Ce script est appelé lors de l'appel de https://dido.geoapi.fr/v1/xxx
  ou de http://localhost/geoapi/dido/api.php/v1/xxx

  En fonction de l'extension, l'export est effectué en JSON-LD (.jsonld), Turtle (.ttl) ou RDF/XML (.rdf).
  Il existe aussi un format html qui est du Turtle structuré en HTML.
journal: |
  18/7/2021:
    - ajout export au formats turtle, rdf ou html
  9-10/7/2021:
    - ajout référentiels et nomenclatures
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
  - refnoms.inc.php
  - pagination.inc.php
  - ../../phplib/pgsql.inc.php
*/
require __DIR__.'/themesdido.inc.php';
require __DIR__.'/geozones.inc.php';
require __DIR__.'/frequency.inc.php';
require __DIR__.'/catalog.inc.php';
require __DIR__.'/refnoms.inc.php';
require __DIR__.'/pagination.inc.php';
require __DIR__.'/vendor/autoload.php';
require __DIR__.'/../../phplib/pgsql.inc.php';


// génération du contexte - dcatcontext.jsonld
if (in_array($_SERVER['PHP_SELF'], ['/geoapi/dido/api.php/v1/dcatcontext.jsonld', '/v1/dcatcontext.jsonld'])) {
  header('Content-type: application/ld+json; charset="utf-8"');
  die(file_get_contents(__DIR__.'/dcatcontext.json'));
}


// génération du contexte - dcatcontext.(jsonld|ttl|rdf)
if (!preg_match('!((/geoapi/dido/api\.php)?/v1/dcatexport)(\.jsonld|\.ttl|\.rdf|\.html)!', $_SERVER['PHP_SELF'], $matches)) {
  header("HTTP/1.0 404 Not Found");
  header('Content-type: text/plain; charset="utf-8"');
  die("No match for '$_SERVER[PHP_SELF]'\n");
}

//print_r($matches);

$format = $matches[3];

// Url appelée sans le format
$selfUrl = (($_SERVER['SERVER_NAME']=='localhost') ? 'http://' : 'https://').$_SERVER['SERVER_NAME']
  .$matches[1];
//echo "selfUrl=$selfUrl\n";

// dcatexport.(jsonld|ttl|rdf)

// ouverture de la base PgSql en fonction du serveur
if (($_SERVER['SERVER_NAME']=='localhost')) // en localhost sur le Mac
  PgSql::open('pgsql://docker@pgsqlserver/gis/public');
else // sur le serveur dido.geoapi.fr
  PgSql::open('pgsql://benoit@db207552-001.dbaas.ovh.net:35250/datagouv/public');


$pagination = new Pagination();

$graph = [];

// Lecture des ressources stockées en base et fabrication de la liste des URI des datasets DCAT
$datasetUris = [];
if ($pagination->sql) {
  foreach (PgSql::query($pagination->sql) as $tuple) {
    //echo "$tuple[dcat]\n";
    $resource = json_decode($tuple['dcat'], true);
    $graph[] = $resource;
    if (isset($resource['@type'])
        && ((is_string($resource['@type']) && ($resource['@type']=='Dataset'))
             || (is_array($resource['@type']) && in_array('Dataset', $resource['@type'])))) {
      $datasetUris[] = $resource['@id'];
    }
  }
}

if ($pagination->page == 1) {
  $graph = array_merge(
    ThemeDido::jsonld(), // Les thèmes DiDo et du Scheme
    GeoZone::jsonld(), // Les GéoZones
    Frequency::jsonld(), // Les valeurs de fréquence
    [catalog(array_merge($pagination->refNomDsUrisSel, $datasetUris), $pagination)],
    RefNom::jsonld($pagination->refNomDsUrisSel), // Les référentiels et nomenclatures
    $graph // Les ressources DCAT stockées en base
  );
}
else {
  $graph = array_merge(
    [catalog(array_merge($pagination->refNomDsUrisSel, $datasetUris), $pagination)],
    RefNom::jsonld($pagination->refNomDsUrisSel), // Les référentiels et nomenclatures
    $graph // Les ressources DCAT stockées en base
  );
}

// Génération du JSON-LD de l'export du catalogue DCAT
$json = json_encode(
  [
    '@context'=> 'https://dido.geoapi.fr/v1/dcatcontext.jsonld',
    '@graph'=> $graph,
  ],
  JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE
);

// En localhost je remplace les URL par des URL locales pour faciliter les tests en local
if (($_SERVER['SERVER_NAME']=='localhost')) // en localhost sur le Mac
  $json = str_replace(
      [
        'https://dido.geoapi.fr/v1',
        'https://dido.geoapi.fr/id',
      ],
      [
        'http://localhost/geoapi/dido/api.php/v1',
        'http://localhost/geoapi/dido/id.php',
      ],
      $json
    );

switch($format) {
  case '.jsonld': {
    header('Content-type: application/ld+json; charset="utf-8"');
    die($json);
  }
  case '.ttl': {
    header('Content-type: text/plain; charset="utf-8"');
    $data = new \EasyRdf\Graph("$selfUrl.jsonld");
    $data->parse($json, 'jsonld', "$selfUrl.jsonld");
    die($data->serialise('turtle'));
  }
  case '.rdf': {
    header('Content-type: application/rdf+xml; charset="utf-8"');
    $data = new \EasyRdf\Graph("$selfUrl.jsonld");
    $data->parse($json, 'jsonld', "$selfUrl.jsonld");
    die($data->serialise('rdf'));
  }
  case '.html': {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>dcatexport.ttl</title></head><body><pre>\n";
    $data = new \EasyRdf\Graph("$selfUrl.jsonld");
    $data->parse($json, 'jsonld', "$selfUrl.jsonld");
    echo str_replace('<', '&lt', $data->serialise('turtle'));
    die();
  }
}
