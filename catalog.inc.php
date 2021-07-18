<?php
/*PhpDoc:
name: catalog.inc.php
title: catalog.inc.php - Génération de l'objet JSON-LD du catalogue
doc: |
journal: |
  8/7/2021:
    - ajout du champ @context
    - chgt retour de catalog()
  6/7/2021:
    - création
*/
// retourne l'objet Catalog comme objet JSON-LD
function catalog(array $datasetUris, Pagination $pag): array {
  $catalog = [
    '@id'=> 'https://dido.geoapi.fr/id/catalog',
    '@type'=> ($pag->page_size == 'all') ? 'Catalog' : ['Catalog', 'hydra:Collection'],
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
  if ($pag->page_size <> 'all') {
    $catalog['totalItems'] = $pag->totalItems; // nbre total de Datasets
    $selfUrl = (($_SERVER['SERVER_NAME']=='localhost') ? 'http://' : 'https://').$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
    $catalog['view'] = [
      '@id'=> "$selfUrl?page=$pag->page&page_size=$pag->page_size",
      '@type'=> 'hydra:PartialCollectionView',
      'first'=> "$selfUrl?page=1&page_size=$pag->page_size",
    ];
    if ($pag->page > 1)
      $catalog['view']['previous'] = "$selfUrl?page=".($pag->page-1)."&page_size=$pag->page_size";
    $lastPage = floor($pag->totalItems / $pag->page_size) + 1;
    if ($pag->page < $lastPage)
      $catalog['view']['next'] = "$selfUrl?page=".($pag->page+1)."&page_size=$pag->page_size";
    $catalog['view']['last'] = "$selfUrl?page=$lastPage&page_size=$pag->page_size";
  }
  return $catalog;
}
