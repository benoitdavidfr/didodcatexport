# Correspondances de DiDo en DCAT

mise à jour: 7/7/2021 17h30 (en cours)

## Correspondances entre classes d'objets

L'export DCAT nécessite tout d'abord de définir une correspondance entre les classes du catalogue de DiDo
vers celles définies dans DCAT.

Le catalogue de DiDo définit les classes d'objets suivantes:

  - jeu de données (dataset),
  - fichiers annexes (attachments)
  - fichiers de données (datafile)
  - organisation (organization)
  - millesime
  - theme
  - mot-clé

Le standard DCAT (https://www.w3.org/TR/vocab-dcat-2/) est fondé notamment sur les classes suivantes:

  - dcat:Dataset
  - dcat:Distribution
  - dcat:DataService
  - foaf:Agent
  - foaf:Document
  - skos:Concept et skos:ConceptScheme

La correspondance choisie pour les classes est la suivante:

  - jeu de données -> dcat:Dataset
  - fichier annexes -> foaf:Document
  - fichier de données -> dcat:Dataset
  - millesime -> dcat:Distribution
  - organisation -> foaf:Agent (Visiblement DCAT-AP exige que l'on utilise la classe foaf:Agent et pas la classe foaf:Organization)
  - thème -> skos:Concept et le vocabulaire des thèmes -> skos:ConceptScheme
  - mot-clé -> rdfs:Literal

Notes:

  - Un jeu de données DiDo sera représenté en DCAT par un dcat:Dataset qui sera composé de fichiers de données au moyen des propriétés dct:hasPart/dct:isPartOf conformément à la recommandations définie
    dans https://joinup.ec.europa.eu/release/dcat-ap-how-model-dataset-series.  
  - Un fichier de données DiDo sera représenté en DCAT par un dcat:Dataset qui fera partie d'un jeu de données DiDo ;
    il définira une propriété dct:conforms_to vers un fichier contenant un schéma JSON du fichier de données.
  - Un catalogue DCAT sera défini et comprendra tous les jeux de données et les fichiers de données.
  - Un fichier annexe sera représenté par un foaf:Document référencé depuis le jeu de données au travers d'une propriété foaf:page ;
    son URI sera l'URL de téléchargement défini par DiDo.
  - Un millesime sera représenté en DCAT par un dcat:Distribution.  
  - Une organisation DiDo sera représentée comme foaf:Agent.  
  - Les thémes DiDo seront structurés en skos:Concept structurés dans un skos:ConceptScheme, la correspondance de ces thèmes vers le vocabulaire data-theme est définie ci-dessous.  
  - Un mot-clé DiDo sera représenté par un rdfs:Literal lié par la propriété dcat:keyword.

Dans un premier temps, il n'est pas envisagé de représenter les API DiDo comme dcat:DataService car cette dernière classe est spécifique de DCAT v2 et encore peu utilisée.
La solution sera d'indiquer dans les dcat:Distribution correspondant à un millésime un lien vers un fichier CSV généré par DiDo.

La suite du document décrit les correspondances des propriétés des classes et dans certains cas des valeurs possibles.

## Correspondance des propriétés ainsi que de certaines valeurs

### Propriétés de Producteur (Organization) -> foaf:Agent

| nom DiDo   |  type  | description               | nom DCAT   | transformation |
|------------|--------|---------------------------|------------|----------------|
| id         | string | Identifiant du producteur | @id        | URI https://dido.geoapi.fr/id/organizations/{id} |
|            |        |                           | @type      | foaf:Agent |
| title      | string | Nom du producteur         | foaf:name  | |
| acronym    | string | Acronyme du producteur    | foaf:nick  | |
| description| string | Description du producteur | rdfs:comment | |

Note:

  - il manque à DiDo la notion de point de contact avec notamment une adresse mail et un numéro de téléphone pour contacter
    une personne qui peut répondre à des questions sur le jeu de données.
    Etant donné l'organisation du SDES, le point de contact pourrait être l'adresse électronique du bureau producteur.

### Propriétés de Jeu de données (Dataset) -> dcat:Dataset

| nom DiDo   |  type  | description               | nom DCAT   | transformation                                  | commentaire |
|------------|--------|---------------------------|------------|-------------------------------------------------|-------------|
| id         | string | Identifiant du jeu        | @id        | URI https://dido.geoapi.fr/id/datasets/{id} |
|            |        |                           | @type      | ['Dataset', 'http://inspire.ec.europa.eu/metadata-codelist/ResourceType/series'] |
| id         | string | Identifiant du jeu        | identifier | |
| title      | string | Titre du jeu de données   | dct:title | |
| description | string | Description du jeu de données | dct:description |  |
| organization | string | Infos sur le producteur du JD | dct:publisher | URI https://dido.geoapi.fr/id/organizations/{id} |
| topic      | string | Thème du jeu de données | dcat:theme | URI https://dido.geoapi.fr/id/themes/{id} + mapping des themes DiDo vers le voc. data-theme |
| tags       | string | Liste des mot-clés du jeu de données | dcat:keyword | |
| license    | string | Licence sous laquelle est publiée le JD | dct:license | Voir ci-dessous la correspondance des valeurs |
| frequency  | string | Fréquence d'actualisation du jeu de données | dct:accrualPeriodicity | URI dans http://publications.europa.eu/resource/authority/frequency selon correspondance définie ci-dessous |
| frequency_date | date-time | Prochaine date d'actualisation du jeu de données | | **Notion absente** |
| spatial/granularity | string | Granularité du jeu de données | | **Notion absente** |
| spatial/zones | string | Liste de zones géographiques du jeu de données (correspond à un identifiant du référentiel geozone) | dct:spatial | Voir ci-dessous la correspondance des valeurs |
| temporal_coverage/start | date - format YYYY-MM-DD | Date de début de la couverture temporelle | dct:temporal/startDate | |
| temporal_coverage/end | date - format YYYY-MM-DD | Date de fin de la couverture temporelle | dct:temporal/endDate | |
| caution    | string | Mise en garde concernant le JD | **A PRECISER** Utiliser éventuellement dct:rights |
| attachments | [Attachment] | La liste des fichiers descriptifs | foaf:page | comme URI l'URL DiDo de téléchargement du fichier |
| created_at | date-time | date de création du jeu de données | dct:created | |
| last_modified | date-time | Date de dernière modification du jeu de données | dct:modified |
| datafiles  | [Datafile] | Liste des fichiers de données | dct:hasPart | liste d'URI https://dido.geoapi.fr/id/datafiles/{id} |

### Valeurs Topic -> http://publications.europa.eu/resource/authority/data-theme
Correspondance des thèmes DiDo vers un thème de data-theme

| thème DiDo    |  URI                                                             | commentaire |
|---------------|------------------------------------------------------------------|-------------|
| Environnement | http://publications.europa.eu/resource/authority/data-theme/ENVI |
| Énergie       | http://publications.europa.eu/resource/authority/data-theme/ENER |
| Transports    | http://publications.europa.eu/resource/authority/data-theme/TRAN |
| Logement      | http://publications.europa.eu/resource/authority/data-theme/SOCI | Population et société |
| Changement climatique |  http://publications.europa.eu/resource/authority/data-theme/ENVI | Environnement |

### Valeurs pour le champ licence

### Valeurs Frequency -> http://publications.europa.eu/resource/authority/frequency
Correspondance des fréquences (valeurs possibles du champ frequency) vers un concept du vocabulaire
http://publications.europa.eu/resource/authority/frequency

| nom DiDo   |  URI                                                                  | commentaire |
|------------|-----------------------------------------------------------------------|-------------|
| daily      |  http://publications.europa.eu/resource/authority/frequency/DAILY     |
| weekly     |  http://publications.europa.eu/resource/authority/frequency/WEEKLY    |
| monthly    |  http://publications.europa.eu/resource/authority/frequency/MONTHLY   |
| quarterly  |  http://publications.europa.eu/resource/authority/frequency/QUARTERLY |
| semiannual |  http://publications.europa.eu/resource/authority/frequency/ANNUAL_2  |
| annual     |  http://publications.europa.eu/resource/authority/frequency/ANNUAL    |
| punctual   |  http://publications.europa.eu/resource/authority/frequency/NEVER     | **Vérifier que le concept DiDo 'punctual' correspond bien au concept NEVER du vocabulaire EU** |
| irregular  |  http://publications.europa.eu/resource/authority/frequency/IRREG     |
| unknown    |  http://publications.europa.eu/resource/authority/frequency/UNKNOWN   |

### Valeurs Zones géographiques

### Fichier descriptif (Attachment) -> foaf:Document

| nom DiDo   |  type  | description               | nom DCAT   | transformation                                  | commentaire |
|------------|--------|---------------------------|------------|-------------------------------------------------|-------------|
| rid        | uuid   | Identifiant du fichier | @id | URI https://dido.geoapi.fr/id/datafiles/{rid} |
| title      | string | Titre du fichier | dct:title | |
| description | string | Description du fichier | dct:description |  |
| published | date-time | Date de publication du fichier | dct:issued |  |
| url | string | Url pour accéder au fichier | | Propriété inutile, l'URL d'accès étant l'URI |
| created_at | date-time | Date de création du fichier  | dct:created | | **Attention fichier Swagger erroné** |
| last_modified | date-time | Date de dernière modification du fichier | dct:modified | | **Attention fichier Swagger erroné** |

### Fichier de données (Datafile) -> dcat:Dataset

| nom DiDo   |  type  | description               | nom DCAT   | transformation                                  | commentaire |
|------------|--------|---------------------------|------------|-------------------------------------------------|-------------|
| rid        | uuid   | Identifiant du fichier    | @id        | URI https://dido.geoapi.fr/id/attachments/{rid} |
| dataset    | Dataset | Jeu de données parent    | dct:isPartOf | |
| title      | string | Titre du fichier          | dct:title  | |
| description | string | Description du fichier   | dct:description | |
| published | date-time | Date de publication du fichier | dct:issued | |
| temporal_coverage/start | date - format YYYY-MM-DD | Date de début de la couverture temporelle | dct:temporal/startDate | |
| temporal_coverage/end | date - format YYYY-MM-DD | Date de fin de la couverture temporelle | dct:temporal/endDate | |
| legal_notice | string | Notice légale concernant le fichier | dct:rights | **A VERIFIER EN PRATIQUE** |
| weburl | string | Url pour accéder à l'interface de visualisation du fichier | dcat:landingPage |  |
| millesimes | [Millesime] | Informations sur les millésimes du fichier | dcat:distribution |  |
| created_at | date-time | Date de création du fichier  | dct:created | |
| last_modified | date-time | Date de dernière modification du fichier | dct:modified | |

### Millésime -> dcat:Distribution

| nom DiDo   |  type  | description               | nom DCAT   | transformation                                  | commentaire |
|------------|--------|---------------------------|------------|-------------------------------------------------|-------------|
| millesime  | string | Le millésime du fichier - format YYYY-MM | @id | URI https://dido.geoapi.fr/id/millesimes/{rid}/{m} |
| title      | string | Titre du fichier | dct:title | |
| date_diffusion | date-time | Date de diffusion du millesime du fichier | dct:issued | |
| rows | integer | Nombre de lignes dans le fichier | | |
| columns | array | Liste des colonnes du fichier | ct:conformsTo | Structuration de la liste des colonnes comme schéma JSON | **Problème d'encodage des unités !** |
| extendedFilters | array | Liste des filtres étendus du fichier | | |
| geoFields | array | Liste des champs disposant d'une géométrie dans le fichier | | Voir la possibilité d'intégrer l'info dans le schéma JSON |
