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
function catalog(array $datasetUris, int $page, int|string $page_size, int $totalItems): array {
  $catalog = [
    '@id'=> 'https://dido.geoapi.fr/id/catalog',
    '@type'=> ($page_size == 'all') ? 'Catalog' : ['Catalog', 'hydra:Collection'],
    'title'=> "Catalogue DiDo",
    'description'=> "Test d'export en DCAT-AP du catalogue DiDo provenant du site école",
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
  if ($page_size <> 'all') {
    $catalog['totalItems'] = $totalItems; // nbre total de Datasets
    $selfUrl = (($_SERVER['SERVER_NAME']=='localhost') ? 'http://' : 'https://').$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
    $catalog['view'] = [
      '@id'=> "$selfUrl?page=$page&page_size=$page_size",
      '@type'=> 'hydra:PartialCollectionView',
      'first'=> "$selfUrl?page=1&page_size=$page_size",
    ];
    if ($page > 1)
      $catalog['view']['previous'] = "$selfUrl?page=".($page-1)."&page_size=$page_size";
    $lastPage = floor($totalItems / $page_size) + 1;
    if ($page < $lastPage)
      $catalog['view']['next'] = "$selfUrl?page=".($page+1)."&page_size=$page_size";
    $catalog['view']['last'] = "$selfUrl?page=$lastPage&page_size=$page_size";
  }
  return $catalog;
}
