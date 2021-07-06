<?php
/*PhpDoc:
name: licenses.inc.php
title: liste des valeurs de license et méthodes associées
doc: |
journal: |
  5/7/2021:
    - création
*/

class License {
  // Mapping valeur DiDo en URI
  const MAPPING = [
    'fr-lo'=> [
      '@id'=> 'https://www.etalab.gouv.fr/licence-ouverte-open-licence',
      '@type'=> 'dct:LicenseDocument',
    ],
    'odbl'=> [
      '@id'=> 'http://opendatacommons.org/licenses/odbl/summary/',
      '@type'=> 'dct:LicenseDocument',
    ],
  ];
  
  // retourne le tableau de mapping
  static function mappingToURI(): array {
    return self::MAPPING;
  }
};
