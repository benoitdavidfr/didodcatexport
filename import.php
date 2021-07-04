<?php
/*PhpDoc:
name: import.php
title: import V2 du catalogue de DiDo et structuration en vue de l'exposition DCAT
doc: |
  Ce script lit les objets DiDo au travers de son API de consultation, les restructure en DCAT
  et les stocke en base PostGis en vue de leur exposition DCAT.

  Les principes d'exposition sont:
   1) l'export DCAT est exposé à l'URL https://dido.geoapi.fr/v1/dcatexport.jsonld
      son contexte sera exposé à l'URL https://dido.geoapi.fr/v1/dcatcontext.jsonld
   2) les objets DiDo sont identifiés par les URI suivants:
    - https://dido.geoapi.fr/id/catalog pour le catalogue
    - https://dido.geoapi.fr/id/datasets/{id} pour les jeux de données DiDo
    - https://dido.geoapi.fr/id/attachments/{rid} pour les fichiers annexes
    - https://dido.geoapi.fr/id/datafiles/{rid} pour les fichiers de données
    - https://dido.geoapi.fr/id/millesimes/{rid}/{m} pour les millésimes
    - https://dido.geoapi.fr/id/organizations/{id} pour les organisations
    - https://dido.geoapi.fr/id/themes/{id} pour les thèmes DiDo
   3) l'objet dcat:Catalog est paginé selon les principes d'une Collection Hydra,
   4) le paramètre page_size de la requête d'exposition correspond au nbre de dcat:Dataset par sous-objet dcat:Catalog
   5) la page contenant un sous-objet dcat:Catalog contient aussi tous les Dataset et autres objets liés qui n'ont pas 
      été fournis dans les pages précédentes.

  Ce script d'import prépare l'exposition en stockant en base Pgsql les objets DCAT, sauf les dcat:Catalog,
  en stockant pour chacun:
   - leur contenu DCAT comme jsonld
   - l'URI de l'item permettant de le retrouver par son URI
   - l'URI du dcat:Dataset auquel l'item est associé

  De plus un cache des pages datasets lues dans DiDo est stocké dans le répertoire import dans des fichiers nommés page{no}.json
  Le répertoire jd contient un fichier par jeu de données DiDo permettant de faciliter la compréhension des données de DiDo.

journal: |
  4/7/2021:
    - amélioration du publisher et du dataset
  1/7/2021:
    - utilisation de pgsql://benoit@db207552-001.dbaas.ovh.net:35250/datagouv/public sur dido.geoapi.fr
  30/6/2021:
    - changement d'approche et démarrage de la V2
  26-27/6/2021:
    - création de la V1 abandonnée
*/
require __DIR__.'/../../phplib/pgsql.inc.php';

//echo '<pre>'; print_r($_SERVER); die();
//echo php_sapi_name(),"<br>\n"; die();
if (php_sapi_name() <> 'cli') { // Utilisation du script en CLI, limite l'exécution aux personnes pouvant se loguer
  die("Erreur: ce script doit être exécuté en CLI<br>\n");
}

define('JSON_OPTIONS', JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

$rootUrl = 'https://datahub-ecole.recette.cloud/api-diffusion/v1'; // url racine de l'API DiDo

// différents mappings de valeurs
$mappings = [
  // mapping des themes DiDo vers le voc. data-theme
  'FromDidoThemeToDataTheme' => [
    'Environnement' => 'http://publications.europa.eu/resource/authority/data-theme/ENVI', // Environnement
    'Énergie' => 'http://publications.europa.eu/resource/authority/data-theme/ENER', // Energie
    'Transports' => 'http://publications.europa.eu/resource/authority/data-theme/TRAN', // Transports
    'Logement' => 'http://publications.europa.eu/resource/authority/data-theme/SOCI', // Population et société
    'Changement climatique' => 'http://publications.europa.eu/resource/authority/data-theme/ENVI', // Environnement
  ],

  // mapping des themes DiDo vers leur uri
  'FromDidoThemeToUri' => [
    'Environnement' => 'https://dido.geoapi.fr/id/themes/environnement',
    'Énergie' => 'https://dido.geoapi.fr/id/themes/energie',
    'Transports' => 'https://dido.geoapi.fr/id/themes/transports',
    'Logement' => 'https://dido.geoapi.fr/id/themes/logement',
    'Changement climatique' => 'https://dido.geoapi.fr/id/themes/Changement_climatique',
  ],

  // mapping des valeurs du champ DiDo frequency vers le vocabulaire http://publications.europa.eu/resource/authority/frequency
  'FromDidoFrequencyToFrequencyVoc' => [
    'daily'=>     'http://publications.europa.eu/resource/authority/frequency/DAILY',
    'weekly'=>    'http://publications.europa.eu/resource/authority/frequency/WEEKLY',
    'monthly'=>   'http://publications.europa.eu/resource/authority/frequency/MONTHLY',
    'quarterly'=> 'http://publications.europa.eu/resource/authority/frequency/QUARTERLY',
    'semiannual'=>'http://publications.europa.eu/resource/authority/frequency/ANNUAL_2',
    'annual'=>    'http://publications.europa.eu/resource/authority/frequency/ANNUAL',
    'punctual'=>  'http://publications.europa.eu/resource/authority/frequency/NEVER',
    'irregular'=> 'http://publications.europa.eu/resource/authority/frequency/IRREG',
    'unknown'=>   'http://publications.europa.eu/resource/authority/frequency/UNKNOWN',
  ],
];

//unlink(__DIR__."/pgsql.json");

/*function storePgInJson(array $dcat, string $itemUri, string $jdUri) {
  file_put_contents(
    __DIR__."/pgsql.json",
    json_encode(['jsonld'=> $dcat, 'itemUri'=> $itemUri, 'jdUri'=> $jdUri], JSON_OPTIONS).",\n",
    FILE_APPEND
  );
}*/

if (1) { // Ouverture PgSql et création de la table didodcat
  if (($_SERVER['HOME']=='/home/bdavid')) // sur le serveur dido.geoapi.fr
    PgSql::open('pgsql://benoit@db207552-001.dbaas.ovh.net:35250/datagouv/public');
  else // en localhost sur le Mac
    PgSql::open('pgsql://docker@pgsqlserver/gis/public');
  PgSql::query("drop table if exists didodcat");
  PgSql::query("create table didodcat(
    uri varchar(256) not null primary key, -- l'URI de l'élément DCAT 
    dsuri varchar(256) not null, -- l'URI du dataset auquel l'élément est rattaché
    dcat jsonb not null -- le contenu de l'élément DCAT structuré en JSON-LD
  )");
  PgSql::query("comment on table didodcat is 'Un n-uplet par element DCAT autre que dcat:Catalog.'");
  PgSql::query("create index didodcat_dsuri on didodcat(dsuri)");
}

// stockage d'un enregistrement dans PgSql, si $ifDoesntExist est vrai alors pas d'erreur
function storePg(array $dcat, string $itemUri, string $dsUri, bool $ifDoesntExist=false) {
  try {
    //$json = json_encode($dcat, JSON_OPTIONS);
    $json = str_replace("'","''", json_encode($dcat, JSON_OPTIONS));
    $sql = "insert into didodcat(uri, dsuri, dcat) values ('$itemUri', '$dsUri', '$json')";
    //echo "<pre>$sql</pre>\n";
    PgSql::query($sql);
  }
  catch(Exception $e) {
    if (!$ifDoesntExist)
      echo "Erreur ",$e->getMessage(),"\n";
  }
}

/* construit une nouvelle valeur selon le mapping qui est un array [$pDest => $pSrce] où:
  - $pDest est le nom du champ dans le nouvel objet
  - $pSrce est un array d'une des formes suivantes:
    - ['field', le nom du champ dans l'objet source]
    - ['val', la valeur]
    - ['uri', un prefixe d'URI, le nom du champ dans l'objet source à concaténer au prefixe]
    - ['urival', un prefixe d'URI, la valeur à concaténer au prefixe]
    - ['mapping', tableau de mapping, nom du champ pour la valeur à utiliser en entrée du mapping]
    - ['multiple', liste d'array mapping]
*/
function buildValWithMapping(array $pSrce, array $srce) {
  switch ($pSrce[0]) {
    case 'field': return $srce[$pSrce[1]] ?? null;
    case 'val': return $pSrce[1];
    case 'uri': return $pSrce[1].$srce[$pSrce[2]];
    case 'urival': return $pSrce[1].$pSrce[2];
    case 'uriarray': {
      $result = [];
      foreach ($pSrce[2] as $elt) {
        $result[] = $pSrce[1].$elt;
      }
      return $result;
    }
    case 'mapping': return $pSrce[1][$srce[$pSrce[2]]];
    case 'object': {
      $object = [];
      foreach ($pSrce[1] as $key => $val) {
        $newval = buildValWithMapping($val, $srce);
        if ($newval !== null)
          $object[$key] = $newval;
      }
      return $object;
    }
    case 'multiple': {
      $result = [];
      foreach ($pSrce[1] as $elt) {
        $result[] = buildValWithMapping($elt, $srce);
      }
      return $result;
    }
    default: die("mot-clé '$pSrce[0]' non reconnu ligne ".__LINE__."\n");
  }
}

// construit un nouvel objet selon le mapping
function buildObjectWithMapping(array $srce, array $mapping): array {
  //echo "buildObjectWithMapping(srce, mapping=",json_encode($mapping),")\n"; 
  $dest = [];
  foreach ($mapping as $pDest => $pSrce) {
    //echo "buildValWithMapping(pSrce=",json_encode($pSrce),")\n";
    if ($destVal = buildValWithMapping($pSrce, $srce))
      $dest[$pDest] = $destVal;
  }
  return $dest;
}

// fabrique l'Organization FOAF correspondant à un publisher DiDo
function buildDcatForPublisher(array $org): array {
  return buildObjectWithMapping($org,
    [
      '@id'=> ['uri', 'https://dido.geoapi.fr/id/organizations/', 'id'],
      '@type'=> ['val', 'Organization'],
      'name'=> ['field', 'title'],
      'nick'=> ['field', 'acronym'],
      'comment'=> ['field', 'description'],
    ]);
}

// fabrique le Dataset DCAT correspondant à un jeu de données DiDo
function buildDcatForJD(array $jd): array {
  global $mappings;
  
  // enregistre l'organization en effet de bord ssi elle n'est pas déjà définie
  storePg(
    buildDcatForPublisher($jd['organization']),
    "https://dido.geoapi.fr/id/organizations/$jd[id]",
    "https://dido.geoapi.fr/id/datasets/$jd[id]",
    true
  );

  $attachmentRids = [];
  foreach ($jd['attachments'] as $attachment)
    $attachmentRids[] = $attachment['rid'];
  $datafileRids = [];
  foreach ($jd['datafiles'] as $datafile)
    $datafileRids[] = $datafile['rid'];
  return buildObjectWithMapping($jd,
    [
      '@id'=> ['uri', 'https://dido.geoapi.fr/id/datasets/', 'id'],
      '@type'=> ['val', ['Dataset', 'http://inspire.ec.europa.eu/metadata-codelist/ResourceType/series']],
      'identifier'=> ['field', 'id'],
      'title'=> ['field', 'title'],
      'description'=> ['field', 'description'],
      'publisher'=> ['urival', 'https://dido.geoapi.fr/id/organizations/', $jd['organization']['id']],
      'theme'=> ['multiple', [
          ['mapping', $mappings['FromDidoThemeToDataTheme'], 'topic'],
          ['mapping', $mappings['FromDidoThemeToUri'], 'topic'],
        ],
      ],
      'keyword'=> ['field', 'tags'],
      'license'=> ['field', 'license'],
      'accrualPeriodicity'=> ['mapping', $mappings['FromDidoFrequencyToFrequencyVoc'], 'frequency'],
      'frequency_date'=> ['field', 'frequency_date'], // Prochaine date d'actualisation du jeu de données
      'spatial_granularity'=> ['val', $jd['spatial']['granularity']], // Granularité du jeu de données
      'spatial'=> ['uriarray', 'https://dido.geoapi.fr/id/organizations/', $jd['spatial']['zones']],
      'temporal'=> ['object', [
          'startDate'=> ['val', $jd['temporal_coverage']['start'] ?? null],
          'endDate'=> ['val', $jd['temporal_coverage']['end'] ?? null],
        ],
      ],
      'caution'=> ['field', 'caution'],
      'page'=> ['uriarray', 'https://dido.geoapi.fr/id/attachments/', $attachmentRids],
      'created'=> ['field', 'created_at'],
      'modified'=> ['field', 'last_modified'],
      'hasPart'=> ['uriarray', 'https://dido.geoapi.fr/id/datafiles/', $datafileRids],
    ]);
}

// fabrique l'élément DCAT correspondant à un fichier attaché
function buildDcatForAttachment(array $attach, array $dataset): array {
  return array_merge(['type'=> 'attachment', 'datasetId'=> $dataset['id']], $attach);
}

// fabrique le Dataset DCAT correspondant à un fichier de données
function buildDcatForDataFile(array $datafile) : array {
  return array_merge(['type'=> 'datafile'], $datafile);
}

// fabrique l'élément DCAT correspondant à un millésime
function buildDcatForMillesime(array $millesime, array $datafile, string $rootUrl) {
  return array_merge(
    [
      'type'=> 'millesime',
      'csvUrl'=> "$rootUrl/datafiles/$datafile[rid]/csv?millesime=$millesime[millesime]"
        ."&withColumnName=true&withColumnDescription=true&withColumnUnit=true"
    ],
    $millesime
  );
}

$pageNo = 1; // no de la page courante lue dans DiDo initialisé à 1
$url = "$rootUrl/datasets?page=$pageNo&pageSize=10"; // url d'accès à la page courante initialisé avec l'url de la première page

while ($url) { // tant qu'il reste au moins une page à aller chercher
  // Copie éventuelle en local si elle n'est pas déjà présente
  $filePath = __DIR__."/import/page$pageNo.json";
  if (!file_exists($filePath)) {
    echo "$url<br>\n";
    $content = file_get_contents($url);
    file_put_contents($filePath, $content);
  }
  else {
    $content = file_get_contents($filePath);
  }
  
  // Traitement de la page courante
  $content = json_decode($content, true);
  foreach ($content['data'] as $jd) { // itération sur les jeux de données
    //print_r($dataset);
    if (1) { // écriture de chaque JD pour faciliter leur visualisation, inutile en fonctionnement normal
      file_put_contents(
        __DIR__."/jd/jd$jd[id].json",
        json_encode($jd, JSON_OPTIONS)
      );
    }
    
    $jdUri = "https://dido.geoapi.fr/id/datasets/$jd[id]";
    storePg(buildDcatForJD($jd), $jdUri, $jdUri);
    
    foreach ($jd['attachments'] as $attach) {
      storePg(buildDcatForAttachment($attach, $jd), "https://dido.geoapi.fr/id/attachments/$attach[rid]", $jdUri);
    }
    foreach ($jd['datafiles'] as $datafile) {
      $dfUri = "https://dido.geoapi.fr/id/datafiles/$datafile[rid]";
      storePg(buildDcatForDataFile($datafile), $dfUri, $dfUri);
      
      foreach ($datafile['millesimes'] as $millesime) {
        storePg(
          buildDcatForMillesime($millesime, $datafile, $rootUrl),
          "https://dido.geoapi.fr/id/millesimes/$datafile[rid]/$millesime[millesime]", $dfUri);
      }
    }
  }
  
  // définition de l'url et du no de la page suivante
  $url = $content['nextPage'];
  $pageNo++;
}
