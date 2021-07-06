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
    - https://dido.geoapi.fr/id/datasets/{id} pour le jeu de données DiDo identifié par {id}
    - https://dido.geoapi.fr/id/attachments/{rid} pour le fichier annexe identifié par {rid}
    - https://dido.geoapi.fr/id/datafiles/{rid} pour le fichier de données identifié par {rid}
    - https://dido.geoapi.fr/id/millesimes/{rid}/{m} pour le millésime identifié par {rid} et {m}
    - https://dido.geoapi.fr/id/organizations/{id} pour l'organisation identifiée par {id}
    - https://dido.geoapi.fr/id/themes/{id} pour le thème DiDo {id}
    - https://dido.geoapi.fr/id/geozones/{id} pour la géozone {id}
    - https://dido.geoapi.fr/id/json-schema/{rid}/{m} pour les schéma JSON du millésime identifié par {rid} et {m}
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

  Remarques sur la structuration DiDo:
    - il manque un point de contact par jeu de données et fichier de données
journal: |
  5/7/2021:
    - plus de violations dans le validataeur EU mais des warnings
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

// fabrique l'Organization FOAF correspondant à un publisher DiDo
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
    buildDcatForPublisher($jd['organization']),
    'https://dido.geoapi.fr/id/organizations/'.$jd['organization']['id'],
    "https://dido.geoapi.fr/id/datasets/$jd[id]",
    true
  );

  /*foreach ($jd['spatial']['zones'] as $spatial)
    echo "spatial: $spatial\n";*/
  
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
      'page'=> ['uriarray', 'https://dido.geoapi.fr/id/attachments/', project($jd['attachments'], 'rid')],
      'created'=> ['val', $jd['created_at']],
      'modified'=> ['val', $jd['last_modified']],
      'hasPart'=> ['uriarray', 'https://dido.geoapi.fr/id/datafiles/', project($jd['datafiles'], 'rid')],
    ]);
}

// fabrique l'élément DCAT correspondant à un fichier attaché
function buildDcatForAttachment(array $attach, array $dataset): array {
  return buildObjectWithMapping($attach,
    [
      '@id'=> ['val', "https://dido.geoapi.fr/id/attachments/$attach[rid]"],
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
        "https://dido.geoapi.fr/id/millesimes/$datafile[rid]/",
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
      '@id'=> ['val', "https://dido.geoapi.fr/id/millesimes/$datafile[rid]/$millesime[millesime]"],
      '@type'=> ['val', 'Distribution'],
      'title'=> ['val', $millesime['title'] ?? null],
      'issued'=> ['val', $millesime['date_diffusion']],
      'conformsTo'=> ['object', [
          '@type'=> ['val', 'dct:Standard'],
          '@id'=> ['val', "https://dido.geoapi.fr/id/json-schema/$datafile[rid]/$millesime[millesime]"],
        
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
      'MediaType'=> ['val', 'text/csv'],
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
      storePg(buildDcatForDataFile($datafile, $jd), $dfUri, $dfUri);
      
      foreach ($datafile['millesimes'] as $millesime) {
        storePg(
          buildDcatForMillesime($millesime, $datafile, $jd, $rootUrl),
          "https://dido.geoapi.fr/id/millesimes/$datafile[rid]/$millesime[millesime]", $dfUri);
      }
    }
  }
  
  // définition de l'url et du no de la page suivante
  $url = $content['nextPage'];
  $pageNo++;
}
