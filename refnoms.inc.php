<?php
/*PhpDoc:
name: refnoms.inc.php
title: initialise et exploite la liste des référentiels et des nomenclatures DiDo
doc: |
  La classe RefNom contient:
    - la définition des listes de référentiels et nomenclatures avec pour chacun son type, son nom et les formats disponibles
    - des modèles de construction des ressources JSON-LD pour le Dataset et la Distribution
  A ce stade seule la distribution CSV est définie.
journal: |
  9-10/7/2021:
    - création, limitée aux distributions CSV
*/
// construit un ensemble d'Uris par concaténation du préfixe $uriPrefix avec chaque valeur de l'ensemble $setOfIds
function setOfUris(string $uriPrefix, array $setOfIds): array {
  $set = [];
  foreach ($setOfIds as $id)
    $set[] = $uriPrefix.$id;
  return $set;
}

class RefNom {
  // Liste des référentiels et nomenclatures
  // sous la forme [id => ['kind'=> ('referentiels'|'nomenclatures'), 'name'=> {name}, ('theme'=> {theme})?, 'formats'=> {formats}]]
  const ITEMS = [
    'polluantsEau' => [
      'kind'=> 'referentiels',
      'name'=> "Référentiel des polluants de l'eau",
      'theme'=> 'Environnement',
      'formats'=> ['csv','json'],
    ],
    'stationsAir' => [
      'kind'=> 'referentiels',
      'name'=> "Référentiel des stations de mesures de la qualité de l'air",
      'theme'=> 'Environnement',
      'formats'=> ['csv','json'],
    ],
    'stationsEsu' => [
      'kind'=> 'referentiels',
      'name'=> "Référentiel des stations de mesures de la qualité des eaux superficielles", // à confirmer
      'theme'=> 'Environnement',
      'formats'=> ['csv','json'],
    ],
    'ports' => [
      'kind'=> 'referentiels',
      'name'=> "Référentiel des ports",
      'theme'=> 'Environnement',
      'formats'=> ['csv','json'],
    ],
    'cog' => [
      'kind'=> 'referentiels',
      'name'=> "Référentiel du COG",
      'formats'=> ['csv','json'],
    ],
    'geozones' => [
      'kind'=> 'referentiels',
      'name'=> "Référentiel des GéoZones",
      'formats'=> ['csv','json'],
    ],
    'bilanEnergie'=> [
      'kind'=> 'nomenclatures',
      'name'=> "Nomenclature du bilan de l'énergie",
      'theme'=> 'Énergie',
      'formats'=> ['csv','json'],
    ],
    'cslFilieres'=> [
      'kind'=> 'nomenclatures',
      'name'=> "Nomenclature des filières du Compte satellite du logement (CSL)",
      'theme'=> 'Logement',
      'formats'=> ['csv','json'],
    ],
    'cslOperations'=> [
      'kind'=> 'nomenclatures',
      'name'=> "Nomenclature des opérations du Compte satellite du logement (CSL)",
      'theme'=> 'Logement',
      'formats'=> ['csv','json'],
    ],
  ];
  
  // modèle de ressource dcat:Dataset JSON-LD paramétré par $id et $item
  static function datasetModel(string $id, array $item): array {
    return [
      '@id'=> "https://dido.geoapi.fr/id/$item[kind]/$id",
      '@type'=> 'Dataset',
      'title'=> $item['name'],
      'description'=> $item['name'],
      'theme'=> isset($item['theme']) ? [
          ThemeDido::mapping('data-theme')[$item['theme']],
          ThemeDido::mapping('uri')[$item['theme']],
        ]
        : [],
      'keyword'=> [$item['kind'] == 'referentiels' ? "Référentiel DiDo" : "Nomenclature DiDo"],
      'license'=> [
        '@id'=> 'https://www.etalab.gouv.fr/licence-ouverte-open-licence',
        '@type'=> 'dct:LicenseDocument',
      ],
      'distribution'=> setOfUris("https://dido.geoapi.fr/id/$item[kind]/$id/distributions/", $item['formats']),
    ];
  }
  
  // modèles de ressource dcat:Distribution JSON-LD
  static function distribModels(string $format, string $id, array $item): array {
    return [
      '@id'=> "https://dido.geoapi.fr/id/$item[kind]/${id}/distributions/$format",
      '@type'=> 'Distribution',
      'title'=> "Téléchargement $format de $item[name]",
      'description'=> "Téléchargement $format de $item[name]",
      'license'=> [
        '@id'=> 'https://www.etalab.gouv.fr/licence-ouverte-open-licence',
        '@type'=> 'dct:LicenseDocument',
      ],
      'mediaType'=> [
          '@id'=> 'https://www.iana.org/assignments/media-types/'
            .match($format) {'csv'=> 'text/csv', 'json'=> 'application/json'},
          '@type'=> 'dct:MediaType',
      ],
      'conformsTo'=> [
        '@id'=> "https://dido.geoapi.fr/id/$item[kind]/$id/distributions/csv/json-schema",
        '@type'=> 'dct:Standard',
      ],
      'accessURL'=> "https://datahub-ecole.recette.cloud/api-diffusion/v1/$item[kind]/$id/$format"
        .(($format == 'csv') ? '?withColumnName=true&withColumnDescription=true' : ''),
      'downloadURL'=> "https://datahub-ecole.recette.cloud/api-diffusion/v1/$item[kind]/$id/$format"
        .(($format == 'csv') ? '?withColumnName=true&withColumnDescription=true' : ''),
    ];
  }
  
  // retourne un référentiel, une nomenclature ou une de leurs distributions à partir de son URI
  static function getByUri(string $uri): ?array {
    if (!preg_match('!^https://dido.geoapi.fr/id/(referentiels|nomenclatures)/([^/]+)(/distributions/([^/]+))?$!', $uri, $matches)) {
      echo "No match ligne ",__LINE__,"\n";
      return null;
    }
    $kind = $matches[1];
    $id = $matches[2];
    $format = $matches[4] ?? null;
    if (!$format) { // référentiel ou nomenclature
      return self::datasetModel($id, self::ITEMS[$id]);
    }
    else { // distribution CSV/JSON/...
      return self::distribModels($format, $id, self::ITEMS[$id]);
    }
  }

  // retourne la liste des URI des jeux de données, cad des référentiels et des nomenclatures
  static function dsUris(): array {
    $uris = [];
    foreach (self::ITEMS as $id => $item) {
      $uris[] = "https://dido.geoapi.fr/id/$item[kind]/$id";
    }
    return $uris;
  }

  // retourne les référentiels, nomenclatures et leurs distributions comme JSON-LD
  static function jsonld(): array {
    $graph = [];
    foreach (self::ITEMS as $id => $item) {
      $graph[] = self::datasetModel($id, $item);
      foreach ($item['formats'] as $format)
        $graph[] = self::distribModels($format, $id, $item);
    }
    return $graph;
  }

  // retourne le schema JSON, utilise le fichier swagger
  static function jsonSchema(string $uri): ?array {
    if (!preg_match(
        '!^https://dido.geoapi.fr/id/(referentiels|nomenclatures)/([^/]+)/distributions/csv/json-schema$!',
        $uri,
        $matches
      )) {
      return null;
    }
    $id = $matches[2];
    $swagger = file_get_contents(__DIR__.'/import/swagger.json');
    $swagger = json_decode($swagger, true);
    $schema = $swagger['components']['schemas']["item_$id"];
    $schema['type'] = 'object';
    return [
      '$schema'=> 'http://json-schema.org/draft-07/schema#',
      '$id'=> $uri,
      'description'=> $schema['description'],
      'type'=> 'object',
      'properties'=> $schema['properties'],
    ];
  }
};
