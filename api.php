<?php
/*PhpDoc:
name: api.php
title: script d'appel de l'export DCAT et de son contexte
doc: |
  Ce script est appelé lors de l'appel de https://dido.geoapi.fr/v1/xxx
  ou de http://localhost/geoapi/dido/api.php/v1/xxx
journal: |
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
require __DIR__.'/../../phplib/pgsql.inc.php';

// génération du contexte - dcatcontext.jsonld
if (in_array($_SERVER['PHP_SELF'], ['/geoapi/dido/api.php/v1/dcatcontext.jsonld', '/v1/dcatcontext.jsonld'])) {
  header('Content-type: application/ld+json; charset="utf-8"');
  die(file_get_contents(__DIR__.'/dcatcontext.json'));
}

// ! dcatexport.jsonld => erreur
elseif (!in_array($_SERVER['PHP_SELF'], ['/geoapi/dido/api.php/v1/dcatexport.jsonld','/v1/dcatexport.jsonld'])) {
  header("HTTP/1.0 404 Not Found");
  header('Content-type: text/plain; charset="utf-8"');
  die("No match for '$_SERVER[PHP_SELF]'\n");
}

// dcatexport.jsonld
$page = $_GET['page'] ?? 1;
$page_size = $_GET['page_size'] ?? 10;

// ouverture de la base PgSql en fonction du serveur
if (($_SERVER['SERVER_NAME']=='localhost')) // en localhost sur le Mac
  PgSql::open('pgsql://docker@pgsqlserver/gis/public');
else // sur le serveur dido.geoapi.fr
  PgSql::open('pgsql://benoit@db207552-001.dbaas.ovh.net:35250/datagouv/public');


$paramsPagination = paramsPagination($page, $page_size);
$sql = $paramsPagination['sql'];
$refNomDsUrisSel = $paramsPagination['refNomDsUrisSel'];
$totalItems = $paramsPagination['totalItems'];

$graph = [];

// Lecture des ressources stockées en base et fabrication de la liste des URI des datasets DCAT
$datasetUris = [];
if ($sql) {
  foreach (PgSql::query($sql) as $tuple) {
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

if ($page == 1) {
  $graph = array_merge(
    ThemeDido::jsonld(), // Les thèmes DiDo et du Scheme
    GeoZone::jsonld(), // Les GéoZones
    Frequency::jsonld(), // Les valeurs de fréquence
    [catalog(array_merge($refNomDsUrisSel, $datasetUris), $page_size, $page, $totalItems)],
    RefNom::jsonld($refNomDsUrisSel), // Les référentiels et nomenclatures
    $graph // Les ressources DCAT stockées en base
  );
}
else {
  $graph = array_merge(
    [catalog(array_merge($refNomDsUrisSel, $datasetUris), $page_size, $page, $totalItems)],
    RefNom::jsonld($refNomDsUrisSel), // Les référentiels et nomenclatures
    $graph // Les ressources DCAT stockées en base
  );
}

// Génération de l'export du catalogue DCAT
header('Content-type: application/ld+json; charset="utf-8"');
$json = json_encode(
  [
    '@context'=> 'https://dido.geoapi.fr/v1/dcatcontext.jsonld',
    '@graph'=> $graph,
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
