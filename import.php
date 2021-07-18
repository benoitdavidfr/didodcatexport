<?php
/*PhpDoc:
name: import.php
title: import.php - import V2 du catalogue de DiDo et structuration en objets DCAT en vue de l'exposition DCAT
doc: |
  Ce script lit les objets DiDo au travers de son API de consultation, les restructure en DCAT
  et les stocke en base PostGis en vue de leur exposition DCAT.

  Le script décompose les objets en éléments DCAT et les stocke en base Pgsql avec pour chacun:
    - l'URI de l'objet permettant de le retrouver par son URI (uri)
    - le numero du dataset auquel l'élément est rattaché, à partir de 0, pour la pagination
    - leur contenu DCAT comme JSON-LD
    - leur contenu DiDo moissonné comme JSON, uniquement pour les millésimes, sinon null

  Au fur et à mesure de la détection des datasets DCAT, un compteur est incrémenté ce qui permet d'associer à chaque élément DCAT
  un numéro de dataset.
  Ce numéro est utilisé dans la pagination pour définir les éléments DCAT à intégrer dans une page.

  L'objet catalogue qui n'existe pas en DiDo n'est donc pas créé dans la base PgSql.
  De même les thèmes et mots-clés ne sont pas créés dans la base PgSql.

  De plus un cache des pages datasets lues dans DiDo est stocké dans le répertoire import dans des fichiers nommés page{no}.json
  Le répertoire jd contient un fichier par jeu de données DiDo pour faciliter la compréhension des données de DiDo.

  Une option a été introduite en verrue pour générer des ordres d'insertion dans une instance CKAN
  et ainsi copier dans cette instance les MD DiDo. Cette option ne fonctionne que sur localhost.
journal: |
  16/7/2021:
    - ajout code pour copier les MD dans un CKAN
      - la création de relations entre JD et DF ne semble pas fonctionner
  10/7/2021:
    - modif. modèle de certains URI
    - modif. signature de storePg()
    - simplif. par chgt algo. des builDcatXXX() en supprimant les buildXXXWithMapping()
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

//define('CKAN_SERVER', 'sopra'); // définition d'un serveur CKAN. Si mis en commentaire alors stockage du DCAT en PgSql

$ckanServer = null; // par défaut, pas de serveur CKAN => stockage en PgSql

//echo '<pre>'; print_r($_SERVER); die();
//echo php_sapi_name(),"<br>\n"; die();
if (php_sapi_name() <> 'cli') { // Restriction de l'exécution du script en CLI pour le restreindre aux personnes loguées
  die("Erreur: ce script doit être exécuté en CLI<br>\n");
}

define('JSON_OPTIONS', JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

$rootUrl = 'https://datahub-ecole.recette.cloud/api-diffusion/v1'; // url racine de l'API DiDo

if (defined('CKAN_SERVER') && ($_SERVER['HOME']<>'/home/bdavid')) { // utilisation d'une instance CKAN avec l'API DcatApi.
  require '../../ckanapi/dcatapi.inc.php';
  $ckanServer = new DcatApi(CKAN_SERVER); 
}
else { // Ouverture PgSql et création de la table didodcat
  if (($_SERVER['HOME']=='/home/bdavid')) // sur le serveur dido.geoapi.fr
    PgSql::open('pgsql://benoit@db207552-001.dbaas.ovh.net:35250/datagouv/public');
  else // en localhost sur le Mac
    PgSql::open('pgsql://docker@pgsqlserver/gis/public');
  PgSql::query("drop table if exists didodcat");
  // Schéma de la table didocat
  PgSql::query("create table didodcat(
    uri varchar(256) not null primary key, -- l'URI de l'élément DCAT 
    dsnum int not null, -- le numero du dataset auquel l'élément est rattaché, à partir de 0
    dcat jsonb not null,  -- le contenu de l'élément traduit en DCAT et structuré en JSON-LD
    dido jsonb -- uniquement pour les millésimes le contenu de l'élément DiDo structuré en JSON, sinon null
  )");
  PgSql::query("comment on table didodcat is 'Un n-uplet par element moissonné dans DiDo'");
  PgSql::query("create index didodcat_dsnum on didodcat(dsnum)");
}

// stocke un enregistrement dans PgSql, si $ifDoesntExist est vrai alors n'affiche pas d'erreur
function storePg(array $dcat, string $dsUri, array $dido=[], bool $ifDoesntExist=false) {
  // enregistrement des URI des Datasets pour définir un numéro de dataset utilisé dans la pagination
  static $dsUris = []; // [$dsUri => no]
  
  if (!isset($dsUris[$dsUri]))
    $dsUris[$dsUri] = count($dsUris);
  $dsNum = $dsUris[$dsUri];
    
  try {
    $didoJson = $dido ? "'".str_replace("'","''", json_encode($dido, JSON_OPTIONS))."'" : 'null';
    $dcatJson = "'".str_replace("'","''", json_encode($dcat, JSON_OPTIONS))."'";
    $sql = "insert into didodcat(uri, dsnum, dcat, dido) values ('".$dcat['@id']."', $dsNum, $dcatJson, $didoJson)";
    //echo "<pre>$sql</pre>\n";
    PgSql::query($sql);
  }
  catch(Exception $e) {
    if (!$ifDoesntExist)
      echo "Erreur ",$e->getMessage(),"\n";
  }
}

// supprime les champs de l'objet ayant null comme valeur
function deleteNullValueFromObject(array $object): array {
  $result = [];
  foreach ($object as $key => $value)
    if (!is_null($value))
      $result[$key] = $value;
  return $result;
}

// Un mapping un tableau de correspondance qui associe une valeur à une chaine, le tableau est stocké comme array Php
// applique un mapping à une valeur en levant une exception en cas d'absence de mapping
function mapsAVal(array $mapping, string $val) {
  if (isset($mapping[$val]))
    return $mapping[$val];
  else
    throw new Exception("Erreur de mapping sur $val");
}

// applique un mapping à chacune des valeurs d'un ensemble pour construire un nouvel ensemble en levant évt. une exception
function mapsASet(array $mapping, array $setOfVals): array {
  $result = [];
  foreach ($setOfVals as $elt) {
    if (isset($mapping[$elt]))
      $result[] = $mapping[$elt];
    else
      throw new Exception("Erreur de mapping sur $elt");
  }
  return $result;
}

// construit un ensemble d'Uris par concaténation du préfixe $uriPrefix avec chaque valeur de l'ensemble $setOfIds
function setOfUris(string $uriPrefix, array $setOfIds): array {
  $set = [];
  foreach ($setOfIds as $id)
    $set[] = $uriPrefix.$id;
  return $set;
}

// construit l'ensemble des valeurs du champ $field de chaque objet de $setOfObjects
function project(string $field, array $setOfObjects): array {
  $result = [];
  foreach ($setOfObjects as $object) {
    $result[] = $object[$field];
  }
  return $result;
}

// fabrique l'Agent FOAF correspondant à un publisher DiDo
function buildDcatForPublisher(array $org): array {
  return [
    '@id'=> "https://dido.geoapi.fr/id/organizations/$org[id]",
    //'@type'=> 'Organization', -> génère une violation, DCAT-AP exige un foaf:Agent
    '@type'=> 'Agent',
    'name'=> $org['title'],
    'nick'=> $org['acronym'],
    'comment'=> $org['description'],
  ];
}

// fabrique le Dataset DCAT correspondant à un jeu de données DiDo
function buildDcatForJD(array $jd): array {
  if (!defined('CKAN_SERVER')) {
    // enregistre l'organization en effet de bord ssi elle n'est pas déjà définie
    storePg(
      buildDcatForPublisher($jd['organization']),
      "https://dido.geoapi.fr/id/datasets/$jd[id]",
      [],
      true
    );
  }

  return [
    '@id'=> "https://dido.geoapi.fr/id/datasets/$jd[id]",
    '@type'=> ['Dataset', 'http://inspire.ec.europa.eu/metadata-codelist/ResourceType/series'],
    'identifier'=> $jd['id'],
    'title'=> $jd['title'],
    'description'=> $jd['description'],
    'publisher'=> 'https://dido.geoapi.fr/id/organizations/'.$jd['organization']['id'],
    'theme'=> [
      mapsAVal(ThemeDido::mapping('data-theme'), $jd['topic']), // le thème de data-theme
      mapsAVal(ThemeDido::mapping('uri'), $jd['topic']), // le theme DiDo sous la forme d'un URI
    ],
    'keyword'=> $jd['tags'],
    'license'=> mapsAVal(License::mappingToURI(), $jd['license']),
    'accrualPeriodicity'=> mapsAVal(Frequency::mappingToURI(), $jd['frequency']),
    'frequency_date'=> $jd['frequency_date'] ?? null, // Prochaine date d'actualisation du jeu de données
    'spatialGranularity'=> $jd['spatial']['granularity'], // Granularité du jeu de données
    'spatial'=> mapsASet(Geozone::mappingToUri(), $jd['spatial']['zones']),
    'temporal'=> [
      '@type'=> 'dct:PeriodOfTime',
      'startDate'=> $jd['temporal_coverage']['start'],
      'endDate'=> $jd['temporal_coverage']['end'],
    ],
    'caution'=> $jd['caution'] ?? null,
    'page'=> project('url', $jd['attachments']),
    'created'=> $jd['created_at'],
    'modified'=> $jd['last_modified'],
    'hasPart'=> setOfUris('https://dido.geoapi.fr/id/datafiles/', project('rid', $jd['datafiles'])),
    //'dido'=> $jd,
  ];
}

// fabrique le Document DublinCore correspondant à un fichier attaché
function buildDcatForAttachment(array $attach, array $dataset): array {
  return [
    '@id'=> $attach['url'],
    '@type'=> 'Document',
    'title'=> $attach['title'],
    'description'=> $attach['description'],
    'issued'=> $attach['published'],
    'created'=> $attach['created_at'],
    'modified'=> $attach['last_modified'],
  ];
}

// fabrique le Dataset DCAT correspondant à un fichier de données
function buildDcatForDataFile(array $datafile, array $dataset) : array {
  return deleteNullValueFromObject([
    '@id'=> "https://dido.geoapi.fr/id/datafiles/$datafile[rid]",
    '@type'=> 'Dataset',
    'isPartOf'=> "https://dido.geoapi.fr/id/datasets/$dataset[id]",
    'title'=> $datafile['title'],
    'description'=> $datafile['description'],
    'issued'=> $datafile['published'],
    'temporal'=> [
      '@type'=> 'dct:PeriodOfTime',
      'startDate'=> $datafile['temporal_coverage']['start'],
      'endDate'=> $datafile['temporal_coverage']['end'],
    ],
    'license'=> mapsAVal(License::mappingToURI(), $dataset['license']),
    'rights'=> $datafile['legal_notice'] ?? null,
    'landingPage'=> [
      '@id'=> $datafile['weburl'],
      '@type'=> 'foaf:Document',
    ],
    'distribution'=> setOfUris(
      "https://dido.geoapi.fr/id/datafiles/$datafile[rid]/millesimes/",
      project('millesime', $datafile['millesimes'])
    ),
    'created'=> $datafile['created_at'],
    'modified'=> $datafile['last_modified'],
  ]);
}

// fabrique l'élément DCAT correspondant à un millésime
function buildDcatForMillesime(array $millesime, array $datafile, array $dataset, string $rootUrl): array {
  return [
    '@id'=> "https://dido.geoapi.fr/id/datafiles/$datafile[rid]/millesimes/$millesime[millesime]",
    '@type'=> 'Distribution',
    'title'=> "Millésime $millesime[millesime] en CSV avec pour chaque colonne son nom, sa description et son unité",
    'issued'=> $millesime['date_diffusion'],
    'conformsTo'=> [
      '@type'=> 'dct:Standard',
      '@id'=> "https://dido.geoapi.fr/id/datafiles/$datafile[rid]/millesimes/$millesime[millesime]/json-schema",
    ],
    'license'=> mapsAVal(License::mappingToURI(), $dataset['license']),
    'downloadURL'=> "$rootUrl/datafiles/$datafile[rid]/csv?millesime=$millesime[millesime]"
        ."&withColumnName=true&withColumnDescription=true&withColumnUnit=true",
    'accessURL'=> "$rootUrl/datafiles/$datafile[rid]/csv?millesime=$millesime[millesime]"
        ."&withColumnName=true&withColumnDescription=true&withColumnUnit=true",
    'mediaType'=> ['@id'=> 'https://www.iana.org/assignments/media-types/text/csv', '@type'=> 'dct:MediaType'],
  ];
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
    $jdDcat = buildDcatForJD($jd);
    if ($ckanServer) {
      $ckanServer->dataset_delete("ds$jd[id]");
      $result = $ckanServer->dataset_create("ds$jd[id]", $jdDcat);
      print_r($result);
    }
    else
      storePg($jdDcat, $jdUri);
    
    foreach ($jd['attachments'] as $attach) {
      if (!$ckanServer)
        storePg(buildDcatForAttachment($attach, $jd), $jdUri);
    }
    foreach ($jd['datafiles'] as $datafile) {
      $dfUri = "https://dido.geoapi.fr/id/datafiles/$datafile[rid]";
      $dfDcat = buildDcatForDataFile($datafile, $jd);
      if ($ckanServer) {
        $ckanServer->dataset_delete("df$datafile[rid]");
        print_r($ckanServer->dataset_create("df$datafile[rid]", $dfDcat));
        print_r(
          $ckanServer->dataset_relationship_create("df$datafile[rid]", "ds$jd[id]", 'child_of', "Is a datafile of the dataset")
        );
      }
      else
        storePg($dfDcat, $dfUri);
      
      foreach ($datafile['millesimes'] as $millesime) {
        if (!$ckanServer)
          storePg(buildDcatForMillesime($millesime, $datafile, $jd, $rootUrl), $dfUri, $millesime);
      }
    }
  }
  
  // définition de l'url et du no de la page suivante
  $url = $content['nextPage'];
  $pageNo++;
}

{ // import du fichier Swagger
  $filePath = __DIR__."/import/swagger.json";
  $url = 'https://datahub-ecole.recette.cloud/api-diffusion/v1/swagger.json';
  if (!file_exists($filePath)) {
    echo "$url<br>\n";
    $content = file_get_contents($url);
    file_put_contents($filePath, $content);
  }
}