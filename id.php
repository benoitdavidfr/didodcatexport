<?php
/*PhpDoc:
name: id.php
title: script appelé lors de l'appel des URI définis dans l'export DCAT
doc: |
  Ce script est appelé lors de l'appel d'un des URI
    - https://dido.geoapi.fr/id/catalog pour le catalogue
    - https://dido.geoapi.fr/id/datasets/{id} pour le jeu de données DiDo {id} (dcat:Dataset)
    - https://dido.geoapi.fr/id/attachments/{rid} pour les fichiers annexe {rid} (foaf:Document)
    - https://dido.geoapi.fr/id/datafiles/{rid} pour le fichier de données {rid} (dcat:Dataset)
    - https://dido.geoapi.fr/id/millesimes/{rid}/{m} pour le millésime {m} du fichier de données {rid} (dcat:Distribution)
    - https://dido.geoapi.fr/id/organizations/{id} pour l'organisation {id} (foaf:Organzation)
    - https://dido.geoapi.fr/id/themes pour les thèmes DiDo (skos:ConceptScheme)
    - https://dido.geoapi.fr/id/themes/{id} pour le thème DiDo {id} (skos:Concept)
    - https://dido.geoapi.fr/id/json-schema/{rid}/{m} pour le schéma JSON du millésime {m} du fichier de données {rid} (foaf:Document)

  Il peut être appelé pour des tests locaux à l'adresse:
    - https://localhost/geoapi/dido/id.php/...
  Exemples:
    - https://dido.geoapi.fr/id/organizations/60abeb7b17967d0023c883a2
    - http://localhost/geoapi/dido/id.php/organizations/60abeb7b17967d0023c883a2
journal: |
  6-7/7/2021:
    - améliorations
  1/7/2021:
    - première version assez complète
*/
require __DIR__.'/catalog.inc.php';
require __DIR__.'/themesdido.inc.php';
require __DIR__.'/../../phplib/pgsql.inc.php';

$pattern = '!^(/geoapi/dido/id.php/|/id/)'
    .'(catalog'
    .'|(datasets|attachments|datafiles|organizations)/[^/]+'
    .'|(millesimes|json-schema)/[^/]+/[^/]+'
    .'|(themes)(/[^/]+)?'
    .')$!';
if (!preg_match($pattern, $_SERVER['REQUEST_URI'], $matches)) {
  header("HTTP/1.0 404 Not Found");
  header('Content-type: text/plain; charset="utf-8"');
  die("No match for '$_SERVER[REQUEST_URI]'\n");
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
  $datasetUris = []; // Liste des URI des Dataset DCAT
  foreach (PgSql::query("select dcat from didodcat") as $tuple) {
    //echo "$tuple[dcat]\n";
    $resource = json_decode($tuple['dcat'], true);
    if (isset($resource['@type'])
        && ((is_string($resource['@type']) && ($resource['@type']=='Dataset'))
             || (is_array($resource['@type']) && in_array('Dataset', $resource['@type'])))) {
      $datasetUris[] = $resource['@id'];
    }
  }
  
  header('Content-type: application/json; charset="utf-8"');
  die(json_encode(catalog($datasetUris), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
}

// Un theme ou les themes
elseif (preg_match('!^themes(/([^/]+))?$!', $param, $matches)) {
  //echo "Affichage Thèmes\n";
  //print_r($matches);
  if (!isset($matches[2])) { // pas de theme particulier -> le vocabulaire contrôlé
    header('Content-type: application/json; charset="utf-8"');
    die(json_encode(ThemeDido::themes(), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
  }
  elseif ($result = ThemeDido::theme($uri)) {
    header('Content-type: application/json; charset="utf-8"');
    die(json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
  }
  else {
    header("HTTP/1.0 404 Not Found");
    header('Content-type: text/plain; charset="utf-8"');
    die("Erreur, URI $uri absente\n");
  }
}

// Un schema json
elseif (preg_match('!^json-schema/([^/]+)/([^/]+)$!', $param, $matches)) {
  header('Content-type: text/plain; charset="utf-8"');
  echo "Affichage json-schema\n";
}

// Elt DCAT stockés en base
else {
  $tuples = PgSql::getTuples("select dcat from didodcat where uri='$uri'");
  if (count($tuples) > 0) { // élément trouvé
    header('Content-type: application/json; charset="utf-8"');
    die($tuples[0]['dcat']);
  }
  else { // élément non trouvé
    header("HTTP/1.0 404 Not Found");
    header('Content-type: text/plain; charset="utf-8"');
    die("Erreur, URI $uri absente\n");
  }
}
