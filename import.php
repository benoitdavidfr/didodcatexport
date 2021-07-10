<?php
/*PhpDoc:
name: import.php
title: import V2 du catalogue de DiDo et structuration en vue de l'exposition DCAT
doc: |
  Ce script lit les objets DiDo au travers de son API de consultation, les restructure en DCAT
  et les stocke en base PostGis en vue de leur exposition DCAT.

  Le script décompose les objets en éléments DCAT et stocke chaque élément en base Pgsql avec pour chacun:
   - leur contenu DiDo moissonné comme JSON
   - leur contenu DCAT comme JSON-LD
   - l'URI de l'objet permettant de le retrouver par son URI
   - l'URI du dcat:Dataset auquel l'objet est associé

  De plus un cache des pages datasets lues dans DiDo est stocké dans le répertoire import dans des fichiers nommés page{no}.json
  Le répertoire jd contient un fichier par jeu de données DiDo permettant de faciliter la compréhension des données de DiDo.

  L'objet catalogue n'existe pas en DiDo et n'est donc pas créé dans la base PgSql.
  De même les thèmes et mots-clés ne sont pas créés dans la base PgSql.
journal: |
  10/7/2021:
    - modif. modèle de certains URI
    - modif. signature de storePg()
  8/7/2021:
    - remplacement de l'URI https://dido.geoapi.fr/id/attachments/{rid} en l'URL du fichier
  5/7/2021:
    - plus de violation dans le validataeur EU mais des warnings
  4/7/2021:
    - première version un peu complète à améliorer
      - revoir la licence, le champ spatial, le schéma JSON
    - des violations dans le validataeur EU
  1/7/2021:
    - utilisation de pgsql://benoit@db207552-001.dbaas.ovh.net:35250/datagouv/public sur dido.geoapi.fr
  30/6/2021:
    - changement d'approche et démarrage de la V2
  26-27/6/2021:
    - création de la V1 abandonnée
includes:
  - themesdido.inc.php
  - geozones.inc.php
  - frequency.inc.php
  - licenses.inc.php
  - ../../phplib/pgsql.inc.php
*/
require __DIR__.'/themesdido.inc.php';
require __DIR__.'/geozones.inc.php';
require __DIR__.'/frequency.inc.php';
require __DIR__.'/licenses.inc.php';
require __DIR__.'/../../phplib/pgsql.inc.php';

//echo '<pre>'; print_r($_SERVER); die();
//echo php_sapi_name(),"<br>\n"; die();
if (php_sapi_name() <> 'cli') { // Utilisation du script en CLI pour restreindre l'exécution aux personnes loguées
  die("Erreur: ce script doit être exécuté en CLI<br>\n");
}

define('JSON_OPTIONS', JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

$rootUrl = 'https://datahub-ecole.recette.cloud/api-diffusion/v1'; // url racine de l'API DiDo

{ // Ouverture PgSql et création de la table didodcat
  if (($_SERVER['HOME']=='/home/bdavid')) // sur le serveur dido.geoapi.fr
    PgSql::open('pgsql://benoit@db207552-001.dbaas.ovh.net:35250/datagouv/public');
  else // en localhost sur le Mac
    PgSql::open('pgsql://docker@pgsqlserver/gis/public');
  PgSql::query("drop table if exists didodcat");
  PgSql::query("create table didodcat(
    uri varchar(256) not null primary key, -- l'URI de l'élément DCAT 
    dsuri varchar(256) not null, -- l'URI du dataset auquel l'élément est rattaché
    dido jsonb not null, -- le contenu de l'élément DiDo structuré en JSON
    dcat jsonb not null  -- le contenu de l'élément traduit en DCAT et structuré en JSON-LD
  )");
  PgSql::query("comment on table didodcat is 'Un n-uplet par element moissonné dans DiDo'");
  PgSql::query("create index didodcat_dsuri on didodcat(dsuri)");
}

// stockage d'un enregistrement dans PgSql, si $ifDoesntExist est vrai alors pas d'erreur
function storePg(array $dido, array $dcat, string $dsUri, bool $ifDoesntExist=false) {
  try {
    $didoJson = str_replace("'","''", json_encode($dido, JSON_OPTIONS));
    $dcatJson = str_replace("'","''", json_encode($dcat, JSON_OPTIONS));
    $sql = "insert into didodcat(uri, dsuri, dido, dcat) values ('".$dcat['@id']."', '$dsUri', '$didoJson', '$dcatJson')";
    //echo "<pre>$sql</pre>\n";
    PgSql::query($sql);
  }
  catch(Exception $e) {
    if (!$ifDoesntExist)
      echo "Erreur ",$e->getMessage(),"\n";
  }
}

function buildValWithMapping(array $pSrce, array $srce) {
  /* construit une nouvelle valeur selon $pSrce qui est un array d'une des formes suivantes:
    - ['field', le nom du champ dans l'objet source]
    - ['val', la valeur]
    - ['uri', un prefixe d'URI, le nom du champ dans l'objet source à concaténer au prefixe]
    - ['urival', un prefixe d'URI, la valeur à concaténer au prefixe]
    - ['uriarray', un prefixe d'URI, un array de valeurs à concaténer chacune au prefixe]]
    - ['mapping', liste de mapping, nom du champ pour la valeur à utiliser en entrée du mapping]
    - ['mappingVal', liste de mapping, valeur à utiliser en entrée du mapping]
    - ['mappingSetOnVal', liste de mapping, ensemble des valeurs à utiliser en entrée du mapping]
    - ['object', array [key => mapping où mapping définit le mapping pour le champ key ]]
    - ['multiple', liste d'array mapping]
  */
  switch ($pSrce[0]) {
    //case 'field': return $srce[$pSrce[1]] ?? null;
    case 'val': return $pSrce[1];
    //case 'uri': return $pSrce[1].$srce[$pSrce[2]];
    //case 'urival': return $pSrce[1].$pSrce[2];
    case 'uriarray': {
      $result = [];
      foreach ($pSrce[2] as $elt) {
        $result[] = $pSrce[1].$elt;
      }
      return $result;
    }
    case 'mapping': {
      if (isset($pSrce[1][$srce[$pSrce[2]]]))
        return $pSrce[1][$srce[$pSrce[2]]];
      else {
        echo "Pas de mapping pour ",$srce[$pSrce[2]],"\n";
        return "PAS DE MAPPING pour '".$srce[$pSrce[2]];
      }
    }
    case 'mappingVal': {
      if (isset($pSrce[1][$pSrce[2]]))
        return $pSrce[1][$pSrce[2]];
      else {
        echo "Pas de mapping pour $pSrce[2]\n";
        return "PAS DE MAPPING pour '$pSrce[2]'";
      }
    }
    case 'mappingSetOnVal': {
      $result = [];
      foreach ($pSrce[2] as $elt) {
        if (isset($pSrce[1][$elt])) {
          $result = array_merge($pSrce[1][$elt], $result);
        }
        else {
          echo "Pas de maping pour $elt\n";
          $result[] = "PAS DE MAPPING POUR $elt";
        }
      }
      return $result;
    }
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
    default: throw new Exception("mot-clé '$pSrce[0]' non reconnu ligne ".__LINE__."\n");
  }
}

function buildObjectWithMapping(array $srce, array $mapping): array {
  /* construit un nouvel objet selon le mapping qui est un array [$pDest => $pSrce] où:
    - $pDest est le nom du champ dans le nouvel objet
    - $pSrce est un array à passer à buildValWithMapping()
  */
  //echo "buildObjectWithMapping(srce, mapping=",json_encode($mapping),")\n"; 
  $dest = [];
  foreach ($mapping as $pDest => $pSrce) {
    //echo "buildValWithMapping(pSrce=",json_encode($pSrce),")\n";
    if ($destVal = buildValWithMapping($pSrce, $srce))
      $dest[$pDest] = $destVal;
  }
  return $dest;
}

// prend un array d'objets JSON et le nom d'un champ et renvoie un array des valeurs du champ construit à partir de chaque objet 
function project(array $objects, string $field): array {
  $result = [];
  foreach ($objects as $object) {
    $result[] = $object[$field];
  }
  return $result;
}

// fabrique l'Agent FOAF correspondant à un publisher DiDo
function buildDcatForPublisher(array $org): array {
  return buildObjectWithMapping($org,
    [
      '@id'=> ['val', "https://dido.geoapi.fr/id/organizations/$org[id]"],
      //'@type'=> ['val', 'Organization'], -> génère une violation, DCAT-AP exige un foaf:Agent
      '@type'=> ['val', 'Agent'],
      'name'=> ['val', $org['title']],
      'nick'=> ['val', $org['acronym']],
      'comment'=> ['val', $org['description']],
    ]);
}

// fabrique le Dataset DCAT correspondant à un jeu de données DiDo
function buildDcatForJD(array $jd): array {
  // enregistre l'organization en effet de bord ssi elle n'est pas déjà définie
  storePg(
    $jd['organization'],
    buildDcatForPublisher($jd['organization']),
    "https://dido.geoapi.fr/id/datasets/$jd[id]",
    true
  );

  return buildObjectWithMapping($jd,
    [
      '@id'=> ['val', "https://dido.geoapi.fr/id/datasets/$jd[id]"],
      '@type'=> ['val', ['Dataset', 'http://inspire.ec.europa.eu/metadata-codelist/ResourceType/series']],
      'identifier'=> ['val', $jd['id']],
      'title'=> ['val', $jd['title']],
      'description'=> ['val', $jd['description']],
      'publisher'=> ['val', 'https://dido.geoapi.fr/id/organizations/'.$jd['organization']['id']],
      'theme'=> ['multiple', [
          ['mapping', ThemeDido::mapping('data-theme'), 'topic'], // le thème de data-theme
          ['mapping', ThemeDido::mapping('uri'), 'topic'], // le theme DiDo sous la forme d'un URI
        ],
      ],
      'keyword'=> ['val', $jd['tags']],
      'license'=> ['mapping', License::mappingToURI(), 'license'],
      'accrualPeriodicity'=> ['mapping', Frequency::mappingToURI(), 'frequency'],
      'frequency_date'=> ['val', $jd['frequency_date'] ?? null], // Prochaine date d'actualisation du jeu de données
      'spatialGranularity'=> ['val', $jd['spatial']['granularity']], // Granularité du jeu de données
      'spatial'=> ['mappingSetOnVal', Geozone::mappingToUri(), $jd['spatial']['zones']],
      'temporal'=> ['object', [
          '@type'=> ['val', 'dct:PeriodOfTime'],
          'startDate'=> ['val', $jd['temporal_coverage']['start'] ?? null],
          'endDate'=> ['val', $jd['temporal_coverage']['end'] ?? null],
        ],
      ],
      'caution'=> ['val', $jd['caution'] ?? null],
      'page'=> ['uriarray', '', project($jd['attachments'], 'url')],
      'created'=> ['val', $jd['created_at']],
      'modified'=> ['val', $jd['last_modified']],
      'hasPart'=> ['uriarray', 'https://dido.geoapi.fr/id/datafiles/', project($jd['datafiles'], 'rid')],
    ]);
}

// fabrique l'élément DCAT correspondant à un fichier attaché
function buildDcatForAttachment(array $attach, array $dataset): array {
  return buildObjectWithMapping($attach,
    [
      '@id'=> ['val', $attach['url']],
      '@type'=> ['val', 'Document'],
      'title'=> ['val', $attach['title']],
      'description'=> ['val', $attach['description']],
      'issued'=> ['val', $attach['published']],
      'created'=> ['val', $attach['created_at']],
      'modified'=> ['val', $attach['last_modified']],
    ]);
}

// fabrique le Dataset DCAT correspondant à un fichier de données
function buildDcatForDataFile(array $datafile, array $dataset) : array {
  return buildObjectWithMapping($datafile,
    [
      '@id'=> ['val', "https://dido.geoapi.fr/id/datafiles/$datafile[rid]"],
      '@type'=> ['val', 'Dataset'],
      'isPartOf'=> ['val', "https://dido.geoapi.fr/id/datasets/$dataset[id]"],
      'title'=> ['val', $datafile['title']],
      'description'=> ['val', $datafile['description']],
      'issued'=> ['val', $datafile['published']],
      'temporal'=> ['object', [
          '@type'=> ['val', 'dct:PeriodOfTime'],
          'startDate'=> ['val', $datafile['temporal_coverage']['start'] ?? null],
          'endDate'=> ['val', $datafile['temporal_coverage']['end'] ?? null],
        ],
      ],
      'license'=> ['mappingVal', License::mappingToURI(), $dataset['license']],
      'rights'=> ['val', $datafile['legal_notice'] ?? null],
      'landingPage'=> [
        'val', [
          '@id'=> $datafile['weburl'],
          '@type'=> 'foaf:Document',
        ],
      ],
      'distribution'=> [
        'uriarray',
        "https://dido.geoapi.fr/id/datafiles/$datafile[rid]/millesimes/",
        project($datafile['millesimes'], 'millesime')
      ],
      'created'=> ['val', $datafile['created_at']],
      'modified'=> ['val', $datafile['last_modified']],
    ]);
}

// fabrique l'élément DCAT correspondant à un millésime
function buildDcatForMillesime(array $millesime, array $datafile, array $dataset, string $rootUrl): array {
  return buildObjectWithMapping($millesime,
    [
      '@id'=> ['val', "https://dido.geoapi.fr/id/datafiles/$datafile[rid]/millesimes/$millesime[millesime]"],
      '@type'=> ['val', 'Distribution'],
      'title'=> ['val', $millesime['title'] ?? null],
      'issued'=> ['val', $millesime['date_diffusion']],
      'conformsTo'=> ['object', [
          '@type'=> ['val', 'dct:Standard'],
          '@id'=> ['val', "https://dido.geoapi.fr/id/datafiles/$datafile[rid]/millesimes/$millesime[millesime]/json-schema"],
        
      ]],
      'license'=> ['mappingVal', License::mappingToURI(), $dataset['license']],
      'downloadURL'=> [
        'val',
        "$rootUrl/datafiles/$datafile[rid]/csv?millesime=$millesime[millesime]"
          ."&withColumnName=true&withColumnDescription=true&withColumnUnit=true"
      ],
      'accessURL'=> [
        'val',
        "$rootUrl/datafiles/$datafile[rid]/csv?millesime=$millesime[millesime]"
          ."&withColumnName=true&withColumnDescription=true&withColumnUnit=true"
      ],
      'mediaType'=> ['val', ['@id'=> 'https://www.iana.org/assignments/media-types/text/csv', '@type'=> 'dct:MediaType']],
    ]);
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
    if (0) { // écriture de chaque JD pour faciliter leur visualisation, inutile en fonctionnement normal
      file_put_contents(
        __DIR__."/jd/jd$jd[id].json",
        json_encode($jd, JSON_OPTIONS)
      );
    }
    
    $jdUri = "https://dido.geoapi.fr/id/datasets/$jd[id]";
    storePg($jd, buildDcatForJD($jd), $jdUri);
    
    foreach ($jd['attachments'] as $attach) {
      storePg($attach, buildDcatForAttachment($attach, $jd), $jdUri);
    }
    foreach ($jd['datafiles'] as $datafile) {
      $dfUri = "https://dido.geoapi.fr/id/datafiles/$datafile[rid]";
      storePg($datafile, buildDcatForDataFile($datafile, $jd), $dfUri);
      
      foreach ($datafile['millesimes'] as $millesime) {
        storePg($millesime, buildDcatForMillesime($millesime, $datafile, $jd, $rootUrl), $dfUri);
      }
    }
  }
  
  // définition de l'url et du no de la page suivante
  $url = $content['nextPage'];
  $pageNo++;
}

{
  $filePath = __DIR__."/import/swagger.json";
  $url = 'https://datahub-ecole.recette.cloud/api-diffusion/v1/swagger.json';
  if (!file_exists($filePath)) {
    echo "$url<br>\n";
    $content = file_get_contents($url);
    file_put_contents($filePath, $content);
  }
}