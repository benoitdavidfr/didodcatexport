<?php
/*PhpDoc:
name: catalog.inc.php
title: Génération de l'objet JSON-LD catalog
doc: |
journal: |
  6/7/2021:
    - création
*/
// retourne l'objet Catalog comme ensemble d'objets JSON-LD
function catalog(array $datasetUris) {
  return [
    [
      '@id'=> 'https://dido.geoapi.fr/id/catalog',
      '@type'=> 'Catalog',
      'title'=> "Catalogue DiDo",
      'description'=> "Test d'export en DCAT-AP du catalogue DiDo provenant du site école ; l'export est formatté en JSON-LD",
      'dataset'=> $datasetUris,
      'homepage'=> [
        '@id'=> 'https://dido.geoapi.fr/', // indiquer ici la page d'accueil du catalogue
        '@type'=> 'foaf:Document',
      ],
      'language'=> [
        '@id'=> 'http://publications.europa.eu/resource/authority/language/FRA',
        '@type'=> 'dct:LinguisticSystem',
      ],
      'publisher'=> [
        '@id'=> 'https://dido.geoapi.fr/id/organizations/SDES',
        //'@type'=> 'Organization', -> génère une violation du validateur DCAT-AP
        '@type'=> 'Agent',
        'name'=> "Ministère de la transition écologique (MTE), Service des Données et des Etudes Statistiques",
        'nick'=> 'SDES',
        'comment'=> "Le SDES est le service statistique du ministère de la transition écologique. Il fait partie du Commissariat Général au Développement Durable (CGDD)",
      ],
      'themeTaxonomy'=> [
        'http://publications.europa.eu/resource/authority/data-theme',
        'https://dido.geoapi.fr/id/themes',
      ],
    ],
  ];
}
