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
class RefNom {
  // Liste des référentiels et nomenclatures
  // sous la forme [id => ['kind'=> ('referentiels'|'nomenclatures'), 'name'=> {name}, 'formats'=> {formats}]]
  const ITEMS = [
    'polluantsEau' => [
      'kind'=> 'referentiels',
      'name'=> "Référentiel des polluants de l'eau",
      'theme'=> 'Environnement',
      'formats'=> ['csv'],
    ],
    'stationsAir' => [
      'kind'=> 'referentiels',
      'name'=> "Référentiel des stations de mesures de la qualité de l'air",
      'theme'=> 'Environnement',
      'formats'=> ['csv'],
    ],
    'stationsEsu' => [
      'kind'=> 'referentiels',
      'name'=> "Référentiel des stations de mesures de la qualité des eaux superficielles", // à confirmer
      'theme'=> 'Environnement',
      'formats'=> ['csv'],
    ],
    'ports' => [
      'kind'=> 'referentiels',
      'name'=> "Référentiel des ports",
      'theme'=> 'Environnement',
      'formats'=> ['csv'],
    ],
    'cog' => [
      'kind'=> 'referentiels',
      'name'=> "Référentiel du COG",
      'formats'=> ['csv'],
    ],
    'geozones' => [
      'kind'=> 'referentiels',
      'name'=> "Référentiel des GéoZones",
      'formats'=> ['csv'],
    ],
    'bilanEnergie'=> [
      'kind'=> 'nomenclatures',
      'name'=> "Nomenclature du bilan de l'énergie",
      'theme'=> 'Énergie',
      'formats'=> ['csv'],
    ],
    'cslFilieres'=> [
      'kind'=> 'nomenclatures',
      'name'=> "Nomenclature des filières du Compte satellite du logement (CSL)",
      'theme'=> 'Logement',
      'formats'=> ['csv'],
    ],
    'cslOperations'=> [
      'kind'=> 'nomenclatures',
      'name'=> "Nomenclature des opérations du Compte satellite du logement (CSL)",
      'theme'=> 'Logement',
      'formats'=> ['csv'],
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
      'distribution'=> ["https://dido.geoapi.fr/id/$item[kind]/$id/distributions/csv"],
    ];
  }
  
  // modèles de ressource dcat:Distribution JSON-LD
  static function distribModels(string $format, string $id, array $item): array {
    return match($format) {
      'csv'=> [
        '@id'=> "https://dido.geoapi.fr/id/$item[kind]/${id}/distributions/csv",
        '@type'=> 'Distribution',
        'title'=> "Téléchargement CSV de $item[name]",
        'description'=> "Téléchargement CSV de $item[name]",
        'license'=> [
          '@id'=> 'https://www.etalab.gouv.fr/licence-ouverte-open-licence',
          '@type'=> 'dct:LicenseDocument',
        ],
        'mediaType'=> [
            '@id'=> 'https://www.iana.org/assignments/media-types/text/csv',
            '@type'=> 'dct:MediaType',
        ],
        'conformsTo'=> [
          '@id'=> "https://dido.geoapi.fr/id/$item[kind]/$id/distributions/csv/json-schema",
          '@type'=> 'dct:Standard',
        ],
        'accessURL'=> "https://datahub-ecole.recette.cloud/api-diffusion/v1/$item[kind]/$id/csv?"
          .'withColumnName=true&withColumnDescription=true',
        'downloadURL'=> "https://datahub-ecole.recette.cloud/api-diffusion/v1/$item[kind]/$id/csv?"
          .'withColumnName=true&withColumnDescription=true',
      ],
    };
  }
  
  // remplace les chaines définies par le mapping dans la valeur source qui peut être une chaine ou un array
  /*static public function replace(array $mapping, $source) {
    if (is_string($source)) {
      $result = $source;
      foreach ($mapping as $var => $value) {
        $result = str_replace($var, $value, $result);
      }
    }
    else {
      $result = [];
      foreach ($source as $key => $value) {
        $result[$key] = self::replace($mapping, $value);
      }
    }
    return $result;
  }*/
  
  // retourne un référentiel, une nomenclature ou une de leurs distributions à partir de son URI
  static function getByUri(string $uri): ?array {
    if (!preg_match('!^https://dido.geoapi.fr/id/(referentiels|nomenclatures)/([^/]+)(/distributions/([^/]+))?$!', $uri, $matches)) {
      echo "No match ligne ",__LINE__,"\n";
      return null;
    }
    $kind = $matches[1];
    $id = $matches[2];
    $format = $matches[4] ?? null;
    switch ($format) {
      // référentiel ou nomenclature
      case null : return self::datasetModel($id, self::ITEMS[$id]);
      // distribution CSV
      case 'csv': return self::distribModels('csv', $id, self::ITEMS[$id]);
      default: {
        echo "No match ligne ",__LINE__,"\n";
        return null;
      }
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
      $graph[] = self::distribModels('csv', $id, $item);
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
