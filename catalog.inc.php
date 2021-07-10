<?php
/*PhpDoc:
name: catalog.inc.php
title: Génération de l'objet JSON-LD du catalogue
doc: |
journal: |
  8/7/2021:
    - ajout du champ @context
    - chgt retour de catalog()
  6/7/2021:
    - création
*/
// retourne l'objet Catalog comme objet JSON-LD
function catalog(array $datasetUris) {
  return [
    '@id'=> 'https://dido.geoapi.fr/id/catalog',
    '@type'=> 'Catalog',
    'title'=> "Catalogue DiDo",
    'description'=> "Test d'export en DCAT-AP du catalogue DiDo provenant du site école ; l'export est formatté en JSON-LD",
    'dataset'=> $datasetUris,
    'homepage'=> [
      '@id'=> 'https://github.com/benoitdavidfr/didodcatexport', // indiquer ici la page d'accueil du catalogue
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
    'contactPoint'=> [
      '@type'=> 'vcard:Kind',
      'vcard:fn'=> "Assistance DiDo",
      'vcard:hasEmail'=> [
        '@id'=> 'mailto:support-dido@developpement-durable.gouv.fr',
      ],
    ],
    'themeTaxonomy'=> [
      'http://publications.europa.eu/resource/authority/data-theme',
      'https://dido.geoapi.fr/id/themes',
    ],
  ];
}
