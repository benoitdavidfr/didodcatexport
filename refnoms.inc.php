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
      'formats'=> ['csv'],
    ],
    'stationsAir' => [
      'kind'=> 'referentiels',
      'name'=> "Référentiel des stations de mesures de la qualité de l'air",
      'formats'=> ['csv'],
    ],
    'stationsEsu' => [
      'kind'=> 'referentiels',
      'name'=> "Référentiel des stations de mesures de la qualité des eaux superficielles", // à confirmer
      'formats'=> ['csv'],
    ],
    'ports' => [
      'kind'=> 'referentiels',
      'name'=> "Référentiel des ports",
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
      'formats'=> ['csv'],
    ],
    'cslFilieres'=> [
      'kind'=> 'nomenclatures',
      'name'=> "Nomenclature des filières du Compte satellite du logement (CSL)",
      'formats'=> ['csv'],
    ],
    'cslOperations'=> [
      'kind'=> 'nomenclatures',
      'name'=> "Nomenclature des opérations du Compte satellite du logement (CSL)",
      'formats'=> ['csv'],
    ],
  ];
  // modèle de ressource dcat:Dataset JSON-LD paramétré par ${kind}, ${id} et ${name}
  const DATASET_MODEL = [
    '@id'=> 'https://dido.geoapi.fr/id/${kind}/${id}',
    '@type'=> 'Dataset',
    'title'=> '${name}',
    'description'=> '${name}',
    'license'=> [
      '@id'=> 'https://www.etalab.gouv.fr/licence-ouverte-open-licence',
      '@type'=> 'dct:LicenseDocument',
    ],
    'distribution'=> ['https://dido.geoapi.fr/id/${kind}/${id}/distributions/csv'],
  ];
  // tableau par format des modèles de ressource dcat:Distribution JSON-LD paramétré par ${kind}, ${id} et ${name}
  const DISTRIB_MODELS = [
    'csv'=> [
      '@id'=> 'https://dido.geoapi.fr/id/${kind}/${id}/distributions/csv',
      '@type'=> 'Distribution',
      'title'=> 'Téléchargement CSV de ${name}',
      'license'=> [
        '@id'=> 'https://www.etalab.gouv.fr/licence-ouverte-open-licence',
        '@type'=> 'dct:LicenseDocument',
      ],
      'conformsTo'=> [
        '@id'=> 'https://dido.geoapi.fr/id/${kind}/${id}/distributions/csv/json-schema',
        '@type'=> 'dct:Standard',
      ],
      'accessURL'=> 'https://datahub-ecole.recette.cloud/api-diffusion/v1/${kind}/${id}/csv?'
        .'withColumnName=true&withColumnDescription=true',
      'downloadURL'=> 'https://datahub-ecole.recette.cloud/api-diffusion/v1/${kind}/${id}/csv?'
        .'withColumnName=true&withColumnDescription=true',
    ],
  ];
  
  // remplace les chaines définies par le mapping dans la valeur source qui peut être une chaine ou un array
  static public function replace(array $mapping, $source) {
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
    switch ($format) {
      // référentiel ou nomenclature
      case null : return self::replace(['${kind}'=>$kind, '${id}'=>$id, '${name}'=> self::ITEMS[$id]['name']], self::DATASET_MODEL);
      // distribution CSV
      case 'csv': {
        return self::replace(
          ['${kind}'=>$kind, '${id}'=>$id, '${name}'=> self::ITEMS[$id]['name']],
          self::DISTRIB_MODELS[$format]
        );
      }
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
    foreach (self::ITEMS as $id => $ref) {
      $graph[] = self::replace(['${id}'=> $id, '${name}'=> $ref['name']], self::DATASET_MODEL);
      $graph[] = self::replace(['${id}'=> $id, '${name}'=> $ref['name']], self::DISTRIB_MODELS['csv']);
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
