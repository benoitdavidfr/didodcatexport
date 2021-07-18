<?php
/*PhpDoc:
name: easyrdf.php
title: easyrdf.php - Test de EasyRdf
doc: |
  Test positif de EasyRdf pour convertir du JSON-LD en Turtle ou en RDF/XML
journal: |
  18/7/2021:
    - création
*/
require __DIR__.'/../vendor/autoload.php';

// format de sortie
$outputFormat = 'ttl';
//$outputFormat = 'rdf';
//$outputFormat = 'jsonld';

// source JSON-LD à lire et à convertir dans le format de sortie
//$source = 'http://localhost/geoapi/dido/id.php/catalog';
$source = 'http://localhost/geoapi/dido/api.php/v1/dcatexport.jsonld';


// expression des paramètres page et page_size
$pagNS = (isset($_GET['page']) ? "?page=$_GET[page]" : '')
  .(isset($_GET['page_size']) ? (isset($_GET['page']) ? '&' : '?') . "page_size=$_GET[page_size]" : '');

$data = new \EasyRdf\Graph($source.$pagNS);
$data->load();
switch ($outputFormat) {
  case 'ttl': {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>easyrdf</title></head><body><pre>\n";
    echo str_replace('<', '&lt', $data->serialise($outputFormat));
    die();
  }
  case 'rdf': {
    header('Content-type: application/rdf+xml; charset="utf-8"');
    echo $data->serialise($outputFormat);
    die();
  }
  case 'jsonld': {
    header('Content-type: application/ld+json; charset="utf-8"');
    echo $data->serialise($outputFormat);
    die();
  }
}
