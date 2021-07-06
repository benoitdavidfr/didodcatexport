<?php
/*PhpDoc:
name: frequency.inc.php
title: liste des valeurs de fréquence et méthodes associées
doc: |
journal: |
  5/7/2021:
    - création
*/

class Frequency {
  // Mapping valeur DiDo en URI
  const MAPPING = [
    'daily'=>     'http://publications.europa.eu/resource/authority/frequency/DAILY',
    'weekly'=>    'http://publications.europa.eu/resource/authority/frequency/WEEKLY',
    'monthly'=>   'http://publications.europa.eu/resource/authority/frequency/MONTHLY',
    'quarterly'=> 'http://publications.europa.eu/resource/authority/frequency/QUARTERLY',
    'semiannual'=>'http://publications.europa.eu/resource/authority/frequency/ANNUAL_2',
    'annual'=>    'http://publications.europa.eu/resource/authority/frequency/ANNUAL',
    'punctual'=>  'http://publications.europa.eu/resource/authority/frequency/NEVER',
    'irregular'=> 'http://publications.europa.eu/resource/authority/frequency/IRREG',
    'unknown'=>   'http://publications.europa.eu/resource/authority/frequency/UNKNOWN',
  ];
  
  // retourne le tableau de mapping
  static function mappingToURI(): array {
    return self::MAPPING;
  }
  
  // Renvoit le JSON-LD définissant comme dct:Frequency les URI utilisées
  static function jsonld(): array {
    $result = [];
    foreach (self::MAPPING as $code => $uri) {
      $result[] = [
        '@id'=> $uri,
        '@type'=> 'dct:Frequency',
      ];
    }
    return $result;
  }
};
