<?php
/*PhpDoc:
name: themesdido.inc.php
title: initialise la liste des thèmes DiDo et défini les méthodes utilisées dans l'export (api.php) et id.php
doc: |
  Peut être amélioré en définissant chaque thème par rapport à un data-theme et par rapport à un concept EuroVoc si nécessaire
journal: |
  5/7/2021:
    - création
*/

class ThemeDido {
  const URI_ROOT = 'https://dido.geoapi.fr/id/themes';
  const DATA_THEME_ROOT = 'http://publications.europa.eu/resource/authority/data-theme';
  protected string $uri; // URI du theme dans le Scheme DiDo
  protected string $dataTheme; // URI du thème de data-theme correspondant
  static array $themes; // tableau des thèmes sous la forme [code => ThemeDido]
  
  function __construct(string $key, string $dataTheme) {
    $this->uri = self::URI_ROOT."/$key";
    $this->dataTheme = self::DATA_THEME_ROOT."/$dataTheme";
  }
  
  // Fabrique un des 2 mappings en fonction du nom fourni
  static function mapping(string $mappingName) {
    $result = [];
    foreach (self::$themes as $code => $theme)
      $result[$code] = ($mappingName == 'data-theme') ? $theme->dataTheme : $theme->uri;
    return $result;
  }
  
  // Renvoit le JSON-LD définissant les thèmes DiDo
  static function jsonld(): array {
    $result['scheme'] = [
      '@id'=> self::URI_ROOT,
      '@type'=> 'ConceptScheme',
      'title'=> "Le vocabulaire contrôlé des thèmes DiDo",
      'hasTopConcept'=> [],
    ];
    foreach (self::$themes as $code => $theme) {
      $result[$code] = [
        '@id'=> $theme->uri,
        '@type'=> 'Concept',
        'prefLabel'=> $code,
        'inScheme'=> self::URI_ROOT,
      ];
      $result['scheme']['hasTopConcept'][] = $theme->uri;
    }
    //return $result;
    return array_values($result);
  }
  
  // Renvoit le JSON-LD définissant le vocabulaire contrôlé des thèmes DiDo
  static function themes(): array {
    $result = [
      '@id'=> self::URI_ROOT,
      '@type'=> 'ConceptScheme',
      'title'=> "Le vocabulaire contrôlé des thèmes DiDo",
      'hasTopConcept'=> [],
    ];
    foreach (self::$themes as $code => $theme) {
      $result['hasTopConcept'][] = $theme->uri;
    }
    return $result;
  }
  
  // Renvoit le JSON-LD définissant un des concepts
  static function theme(string $uri): array {
    foreach (self::$themes as $code => $theme) {
      if ($theme->uri == $uri) {
        return [
          '@id'=> $theme->uri,
          '@type'=> 'Concept',
          'prefLabel'=> $code,
          'inScheme'=> self::URI_ROOT,
        ];
      }
    }
    return [];
  }
};

// Initialisation des thèmes DiDo
ThemeDido::$themes = [
  'Environnement' => new ThemeDido('environnement', 'ENVI'),
  'Énergie' => new ThemeDido('energie', 'ENER'),
  'Transports' => new ThemeDido('transports', 'TRAN'),
  'Logement' => new ThemeDido('logement', 'SOCI'), // Population et société
  'Changement climatique' => new ThemeDido('changement_climatique', 'ENVI'),
];

//print_r(ThemeDido::$themes);
//print_r(ThemeDido::mapping('FromDidoThemeToUri'));
//echo "JSON-LD=",json_encode(ThemeDido::jsonld(), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),"\n";
//die();
