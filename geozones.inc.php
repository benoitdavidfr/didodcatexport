<?php
/*PhpDoc:
name: geozones.inc.php
title: liste des GéoZones et méthodes associées
doc: |
  Utilisation d'un code, inspiré de la norme ISO 3166-1 utilisée par le burreau des publications UE,
  dont la liste est la suivante:
    - FXX pour la métropole
    - GLP/MTQ/GUF/REU/MYT pour chacun des DOM
    - FRA pour la métrople + les 5 DOM (conformément à la logique de l'INSEE - http://id.insee.fr/geo/pays/france)
    - SPM/BLM/MAF/WLF/PYF pour chacune des COM
    - NCL pour la Nouvelle Calédonie
    - ATF pour les Terres australes et antarctiques françaises (TAAF)
    - enfin CPT pour l'île de Clipperton
  Ces codes ont l'avantage d'être plus faciles à retenir que ceux de l'Insee.

  Dans la publication RDF, ces codes sont transformés en URI selon la table suivante, dans laquelle je propose,
  pour respecter DCAT-AP d'utiliser si possible les URI définis par l'UE et sinon pour la métropole
  et la métropole + 5 DOM ceux définis par l'Insee dans son espace RDF:
    FXX: http://id.insee.fr/geo/territoireFrancais/franceMetropolitaine
    GLP: http://publications.europa.eu/resource/authority/country/GLP
    MTQ: http://publications.europa.eu/resource/authority/country/MTQ
    GUF: http://publications.europa.eu/resource/authority/country/GUF
    REU: http://publications.europa.eu/resource/authority/country/REU
    MYT: http://publications.europa.eu/resource/authority/country/MYT
    FRA: http://id.insee.fr/geo/pays/france
    SPM: http://publications.europa.eu/resource/authority/country/MYT
    BLM: http://publications.europa.eu/resource/authority/country/BLM
    MAF: http://publications.europa.eu/resource/authority/country/MAF
    WLF: http://publications.europa.eu/resource/authority/country/WLF
    PYF: http://publications.europa.eu/resource/authority/country/PYF
    NCL: http://publications.europa.eu/resource/authority/country/NCL
    ATF: http://publications.europa.eu/resource/authority/country/FQ0
    CPT: http://publications.europa.eu/resource/authority/country/CPT
journal: |
  5/7/2021:
    - création
*/
class GeoZone {
  const NAMES = [
    'GLP'=> "Guadeloupe",
    'MTQ'=> "Martinique",
    'GUF'=> "Guyane",
    'REU'=> "La Réunion",
    'MYT'=> "Mayotte",
  ];
  
  // mapping des geozones utilisées en URI
  static function mappingToUri(): array {
    return [
      'country:fr'=> ['http://id.insee.fr/geo/pays/france'],
      'country-subset:fr:metro'=> ['http://id.insee.fr/geo/territoireFrancais/franceMetropolitaine'],
      'country-subset:fr:drom'=> [
        'http://publications.europa.eu/resource/authority/country/GLP',
        'http://publications.europa.eu/resource/authority/country/MTQ',
        'http://publications.europa.eu/resource/authority/country/GUF',
        'http://publications.europa.eu/resource/authority/country/REU',
        'http://publications.europa.eu/resource/authority/country/MYT',
      ],
    ];
  }

  // Renvoit le JSON-LD définissant les URI utilisées comme dct:Location
  static function jsonld(): array {
    $result = [
      [
        '@id'=> 'http://id.insee.fr/geo/pays/france',
        '@type'=> 'dct:Location',
        'name'=> "La France métropolitaine plus les 5 DROM mais pas les COM",
      ],
      [
        '@id'=> 'http://id.insee.fr/geo/territoireFrancais/franceMetropolitaine',
        '@type'=> 'dct:Location',
        'name'=> "La France métropolitaine",
      ],
    ];
    foreach (['GLP','MTQ','GUF','REU','MYT'] as $zone) {
      $result[] = [
        '@id'=> "http://publications.europa.eu/resource/authority/country/$zone",
        '@type'=> 'dct:Location',
        'name'=> self::NAMES[$zone],
      ];
    }
    return $result;
  }
};

