<?php
/*PhpDoc:
name: id.php
title: id.php - déréférencement des URI définis dans l'export DCAT
doc: |
  Ce script est appelé lors de l'appel d'un des URI de ressource
  Il peut être appelé pour des tests locaux à l'adresse:
    - https://localhost/geoapi/dido/id.php/...
  Exemples:
    - https://dido.geoapi.fr/id/organizations/60abeb7b17967d0023c883a2
    - http://localhost/geoapi/dido/id.php/organizations/60abeb7b17967d0023c883a2
journal: |
  21/7/2021:
    - ajout cas particulier de l'organisation SDES qui n'existe pas dans les organisations DiDo
  8/7/2021:
    - suppression de l'URI https://dido.geoapi.fr/id/attachments/{rid}
    - ajout du champ @context
  6-7/7/2021:
    - améliorations
    - manque les schéma JSON
  1/7/2021:
    - première version assez complète
includes:
  - catalog.inc.php
  - themesdido.inc.php
  - jsonschema.inc.php
  - refnoms.inc.php
  - ../../phplib/pgsql.inc.php
*/
require __DIR__.'/catalog.inc.php';
require __DIR__.'/themesdido.inc.php';
require __DIR__.'/jsonschema.inc.php';
require __DIR__.'/refnoms.inc.php';
require __DIR__.'/pagination.inc.php';
require __DIR__.'/../../phplib/pgsql.inc.php';

function error(string $message) {
  header('HTTP/1.0 404 Not Found');
  header('Content-type: text/plain; charset="utf-8"');
  die("$message\n");
}

$pattern = '!^(/geoapi/dido/id.php/|/id/)'
    .'(catalog'
    .'|(datasets|datafiles|organizations)/[^/]+'
    .'|datafiles/[^/]+/millesimes/[^/]+(/json-schema)?'
    .'|themes(/[^/]+)?'
    .'|(referentiels|nomenclatures)/[^/]+(/formats/[^/]+|/json-schema|)'
    .')$!';
if (!preg_match($pattern, $_SERVER['PHP_SELF'], $matches)) {
  error("No match for '$_SERVER[PHP_SELF]'\n");
}

//echo "<h2>id.php</h2><pre>\n";
//print_r($_SERVER); die();
//echo "REQUEST_URI -> $_SERVER[REQUEST_URI]<br>\n";
//print_r($matches);
$param = $matches[2];
$uri = "https://dido.geoapi.fr/id/$param"; // l'URI de l'élément défini
//echo "URI = $uri\n";

if (($_SERVER['SERVER_NAME']=='localhost')) // en localhost sur le Mac
  PgSql::open('pgsql://docker@pgsqlserver/gis/public');
else // sur le serveur dido.geoapi.fr
  PgSql::open('pgsql://benoit@db207552-001.dbaas.ovh.net:35250/datagouv/public');

if ($param == 'catalog') {
  $pagination = new Pagination;
  
  $datasetUris = []; // Liste des URI des Dataset DCAT
  if ($pagination->sql) {
    foreach (PgSql::query($pagination->sql) as $tuple) {
      //echo "$tuple[dcat]\n";
      $resource = json_decode($tuple['dcat'], true);
      if (isset($resource['@type'])
          && ((is_string($resource['@type']) && ($resource['@type']=='Dataset'))
               || (is_array($resource['@type']) && in_array('Dataset', $resource['@type'])))) {
        $datasetUris[] = $resource['@id'];
      }
    }
  }
  $result = catalog(array_merge($pagination->refNomDsUrisSel, $datasetUris), $pagination);
}

// Un theme ou les themes
elseif (preg_match('!^themes(/([^/]+))?$!', $param, $matches)) {
  //echo "Affichage Thèmes\n";
  //print_r($matches);
  if (!isset($matches[2])) { // pas de theme particulier -> le vocabulaire contrôlé
    $result = ThemeDido::themes();
  }
  elseif (!($result = ThemeDido::theme($uri))) {
    header("HTTP/1.1 404 Not Found");
    header('Content-type: text/plain; charset="utf-8"');
    die("Erreur, URI $uri absente\n");
  }
}

// Un référentiel, une nomenclaure ou leurs distributions ou leurs schema JSON
elseif (preg_match('!^(referentiels|nomenclatures)/[^/]+(/formats/[^/]+|/json-schema|)$!', $param, $matches)) {
  //print_r($matches);
  if ($matches[2]=='/json-schema') { // schema JSON
    if (!($result = RefNom::jsonSchema($uri))) {
      error("Erreur, URI $uri absente\n");
    }
    else {
      header('Content-type: application/json; charset="utf-8"');
      die(json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
    }
  }
  else { // référentiel, nomenclaure ou leurs distributions
    if (!($result = RefNom::getByUri($uri))) {
      error("Erreur, URI $uri absente\n");
    }
  }
}

// Un schema JSON
elseif (preg_match('!^datafiles/([^/]+)/millesimes/([^/]+)/json-schema$!', $param, $matches)) {
  $dfUri = "https://dido.geoapi.fr/id/datafiles/$matches[1]";
  $milUri = "https://dido.geoapi.fr/id/datafiles/$matches[1]/millesimes/$matches[2]";
  //echo "Affichage json-schema de $milUri\n";
  $tuples = PgSql::getTuples("select dido from didodcat where uri='$milUri'");
  if (count($tuples) > 0) { // élément trouvé
    header('Content-type: application/json; charset="utf-8"');
    die(json_encode(
      jsonSchema(json_decode($tuples[0]['dido'], true), $uri),
      JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
  }
  else {
    error("Erreur, json-schema de $milUri absent\n");
  }
}

// Le publisher SDES
elseif ($param == 'organizations/SDES') {
  $result = organizationSDES();
}

// Elt DCAT stockés en base et restitués tel quel
else {
  $tuples = PgSql::getTuples("select dcat from didodcat where uri='$uri'");
  if (count($tuples) > 0) { // élément trouvé
    $result = json_decode($tuples[0]['dcat'], true);
  }
  else { // élément non trouvé
    error("Erreur, URI $uri absente\n");
  }
}

// Rajout du contexte JSON-LD
$result = array_merge(['@context'=> 'https://dido.geoapi.fr/v1/dcatcontext.jsonld'], $result);

header('Content-type: application/ld+json');
if (($_SERVER['SERVER_NAME']=='localhost')) // en localhost sur le Mac
  die(
    str_replace(
      ['https://dido.geoapi.fr/id', 'https://dido.geoapi.fr/v1'],
      ['http://localhost/geoapi/dido/id.php', 'http://localhost/geoapi/dido/api.php/v1'],
      json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)
    )
  );
else // sur le serveur dido.geoapi.fr
  die(json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
