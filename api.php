<?php
/*PhpDoc:
name: api.php
title: api.php - script d'appel de l'export DCAT et de son contexte
doc: |
  Ce script est appelé lors de l'appel de https://dido.geoapi.fr/v1/xxx
  ou de http://localhost/geoapi/dido/api.php/v1/xxx

  En fonction de l'extension, l'export est effectué en JSON-LD (.jsonld), Turtle (.ttl) ou RDF/XML (.rdf).
  Utilise EasyRdf pour effectuer l'éventuelle conversion.
  L'export au format HTML (.html) est permis en utilisant un renvoi vers ../tools/rdfnav.php 
  ce qui crée une dépendance vers ce script.
journal: |
  21/7/2021:
    - rajout du format html en renvoyant vers ../tools/rdfnav.php
    - correction d'un bug sur le renvoi pour format html
  20/7/2021:
    - suppression du format d'export html en raison de l'écriture de rdfnav.php
    - utilisation pour Turtle du type MIME: application/x-turtle
  18/7/2021:
    - ajout export en formats turtle, rdf ou html
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
  header('Content-type: application/ld+json');
  die(file_get_contents(__DIR__.'/dcatcontext.json'));
}

$localhost = ($_SERVER['SERVER_NAME']=='localhost'); // utilisé pour savoir si on est ou non sur localhost

// ! génération de l'export - dcatexport.(jsonld|ttl|rdf|html) => message d'ERREUR
if (!preg_match('!((/geoapi/dido/api\.php)?/v1/dcatexport)(\.jsonld|\.ttl|\.rdf|\.html)!', $_SERVER['PHP_SELF'], $matches)) {
  header("HTTP/1.1 404 Not Found");
  header('Content-type: text/plain; charset="utf-8"');
  echo "No match for '$_SERVER[PHP_SELF]'\n\n";
  echo "Les URL autorisées sont:\n";
  foreach ([
    'dcatexport.jsonld' => "l'export en JSON-LD",
    'dcatcontext.jsonld' => "le contexte JSON-LD",
    'dcatexport.ttl' => "l'export en Turtle",
    'dcatexport.rdf' => "l'export en RDF/XML",
    'dcatexport.html' => "l'affichage en Turtle en HTML",
    ] as $name => $label) {
      echo ' - '.($localhost ? 'http://localhost/geoapi/dido/api.php/v1/' : 'https://dido.geoapi.fr/v1/')."$name pour $label\n";
  }
  die();
}
//print_r($matches);

$format = $matches[3]; // format demandé pour l'export

// Url appelée sans le format
$selfUrl = ($localhost ? 'http://' : 'https://').$_SERVER['SERVER_NAME'].$matches[1];
//echo "selfUrl=$selfUrl\n";

// pour le format html, renvoie vers ../tools/rdfnav.php pour afficher cette URL en JSON-LD
if ($format == '.html') {
  //echo '<pre>'; print_r($_SERVER);
  $rdfnav = ($localhost ? 'http://localhost/geoapi' : 'https://geoapi.fr').'/tools/rdfnav.php';
  $location = "$rdfnav?url=".urlencode("$selfUrl.jsonld".($_SERVER['QUERY_STRING'] ? "?$_SERVER[QUERY_STRING]" : ''));
  header("Location: $location");
  die("Renvoi vers $location");
}

// ouverture de la base PgSql en fonction du serveur
if ($localhost) // en localhost sur le Mac
  PgSql::open('pgsql://docker@pgsqlserver/gis/public');
else // sur le serveur dido.geoapi.fr
  PgSql::open('pgsql://benoit@db207552-001.dbaas.ovh.net:35250/datagouv/public');


$pagination = new Pagination(); // calcule les paramètres de pagination du catalogue

// Lecture en base des listes des URI des datasets DCAT et des ressources
$graph = []; // liste des ressources
$datasetUris = []; // liste des URI des datasets DCAT
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

// construction de l'export en fonction de la pagination
if ($pagination->page == 1) { // la première page est particulière
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

// En localhost remplacement des URL par des URL locales pour faciliter les tests en local
if ($localhost)
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

// affichage en fonction du format demandé
if ($format == '.jsonld') {
  header('Content-type: application/ld+json');
  die($json);
}
else { // sinon une conversion est effectuée avec EasyRdf
  require __DIR__.'/vendor/autoload.php';
  $data = new \EasyRdf\Graph("$selfUrl.jsonld");
  $data->parse($json, 'jsonld', "$selfUrl.jsonld");
  if ($format == '.ttl') {
    header('Content-type: application/x-turtle');
    die($data->serialise('turtle'));
  }
  else {
    header('Content-type: application/rdf+xml; charset="utf-8"');
    die($data->serialise('rdf'));
  }
}
