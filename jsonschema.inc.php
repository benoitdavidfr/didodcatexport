<?php
/*PhpDoc:
name: jsonschema.inc.php
title: construction du schema JSON d'un millésime
doc: |
  cas intéressant:
    http://localhost/geoapi/dido/id.php/datafiles/02622bc1-167c-4089-b14d-69c70b141c32/millesimes/2020-10/json-schema
  Logique pour déterminer le type de la colonne:
    - si une unité est définie alors il s'agit d'un nombre -> { "type": "number" }
    - sinon c'est une chaine -> { "type": "string" }
  On ajoute le champ unit pour l'unité en clair.
journal: |
  8/7/2021:
    - définition d'un schéma JSON
  7/7/2021:
    - création d'un fantome
*/
function jsonSchema(array $dido, string $uri): array {
  $schema = [
    '$schema'=> 'http://json-schema.org/draft-07/schema#',
    '$id'=> $uri,
    'type'=> 'object',
    'properties'=> [],
    'dido'=> $dido,
  ];
  
  foreach ($dido['columns'] as $column) {
    $schema['properties'][$column['name']] = [
      'type'=> $column['unit'] ? 'number' : 'string',
      'unit'=> $column['unit'],
      'description'=> $column['description'],
    ];
  }
  return $schema;
}
