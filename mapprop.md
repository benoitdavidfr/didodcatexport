# Correspondance des propriétés DiDo en DCAT ainsi que de certaines valeurs

modified: 2021-07-04T10:30
### Points à compléter
- traduire le champ license de jeu de données en une forme standard, voir comment c'est fait dans data.gouv
- vérifier avec Christophe que la valeur 'punctual' du vocabulaire des fréquences correspond bien à http://publications.europa.eu/resource/authority/frequency/NEVER
- traduction de spatial/zones de jeu de donnée
- comment intégrer le champ caution du jeu de données ?
- comment intégrer les unités dans le schéma JSON ?

## Producteur (Organization) -> foaf:Organization

| nom DiDo   |  type  | description               | nom DCAT   | transformation |
|------------|--------|---------------------------|------------|----------------|
| id         | string | Identifiant du producteur | @id        | URI https://dido.geoapi.fr/id/organizations/{id} |
|            |        |                           | @type      | Organization |
| title      | string | Nom du producteur         | foaf:name  | |
| acronym    | string | Acronyme du producteur    | foaf:nick  | |
| description| string | Description du producteur | rdfs:comment | |

## Jeu de données (Dataset) -> dcat:Dataset

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
| license    | string | Licence sous laquelle est publiée le JD | dct:license | **A PRECISER** |
| frequency  | string | Fréquence d'actualisation du jeu de données | dct:accrualPeriodicity | URI dans http://publications.europa.eu/resource/authority/frequency selon correspondance définie |
| frequency_date | date-time | Prochaine date d'actualisation du jeu de données | | **Notion absente** |
| spatial/granularity | string | Granularité du jeu de données | | **Notion absente** |
| spatial/zones | string | Liste de zones géographiques du jeu de données (correspond à un identifiant du référentiel geozone) | dct:spatial | **A PRECISER** |
| temporal_coverage/start | date - format YYYY-MM-DD | Date de début de la couverture temporelle | dct:temporal/startDate | |
| temporal_coverage/end | date - format YYYY-MM-DD | Date de fin de la couverture temporelle | dct:temporal/endDate | |
| caution    | string | Mise en garde concernant le JD | **A PRECISER** |
| attachments | [Attachment] | La liste des fichiers descriptifs | foaf:page | URI https://dido.geoapi.fr/id/attachments/{rid} |
| created_at | date-time | date de création du jeu de données | dct:created | |
| last_modified | date-time | Date de dernière modification du jeu de données | dct:modified |
| datafiles  | [Datafile] | Liste des fichiers de données | dct:hasPart | URI https://dido.geoapi.fr/id/datafiles/{id} |

### Topic -> http://publications.europa.eu/resource/authority/data-theme
Correspondance des thèmes DiDo vers un thème de data-theme

| thème DiDo   |  URI                                                              | commentaire |
|------------|---------------------------------------------------------------------|-------------|
| Environnement | http://publications.europa.eu/resource/authority/data-theme/ENVI |
| Énergie       | http://publications.europa.eu/resource/authority/data-theme/ENER |
| Transports    | http://publications.europa.eu/resource/authority/data-theme/TRAN |
| Logement      | http://publications.europa.eu/resource/authority/data-theme/SOCI | Population et société |
| Changement climatique |  http://publications.europa.eu/resource/authority/data-theme/ENVI | Environnement |

### Frequency -> http://publications.europa.eu/resource/authority/frequency
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

## Fichier descriptif (Attachment) -> foaf:Document

| nom DiDo   |  type  | description               | nom DCAT   | transformation                                  | commentaire |
|------------|--------|---------------------------|------------|-------------------------------------------------|-------------|
| rid        | uuid   | Identifiant du fichier | @id | URI https://dido.geoapi.fr/id/datafiles/{rid} |
| title      | string | Titre du fichier | dct:title | |
| description | string | Description du fichier | dct:description |  |
| published | date-time | Date de publication du fichier | dct:issued |  |
| url | string | Url pour accéder au fichier | | Propriété inutile, l'URL d'accès étant l'URI |
| created_at | date-time | Date de création du fichier  | dct:created | | **Attention fichier Swagger erroné** |
| last_modified | date-time | Date de dernière modification du fichier | dct:modified | | **Attention fichier Swagger erroné** |

## Fichier de données (Datafile) -> dcat:Dataset

| nom DiDo   |  type  | description               | nom DCAT   | transformation                                  | commentaire |
|------------|--------|---------------------------|------------|-------------------------------------------------|-------------|
| rid        | uuid   | Identifiant du fichier | @id | URI https://dido.geoapi.fr/id/attachments/{rid} renvoyant vers l'URL du document de la forme https://datahub-ecole.recette.cloud/api-diffusion/files/{rid} |
| title      | string | Titre du fichier | dct:title | |
| description | string | Description du fichier | dct:description |  |
| published | date-time | Date de publication du fichier | dct:issued |  |
| temporal_coverage/start | date - format YYYY-MM-DD | Date de début de la couverture temporelle | dct:temporal/startDate | |
| temporal_coverage/end | date - format YYYY-MM-DD | Date de fin de la couverture temporelle | dct:temporal/endDate | |
| legal_notice | string | Notice légale concernant le fichier | dct:rights | **A VERIFIER EN PRATIQUE** |
| weburl | string | Url pour accéder à l'interface de visualisation du fichier | dcat:landingPage |  |
| millesimes | [Millesime] | Informations sur les millésimes du fichier | dcat:distribution |  |
| created_at | date-time | Date de création du fichier  | dct:created | |
| last_modified | date-time | Date de dernière modification du fichier | dct:modified | |
| dataset    | Dataset | Jeu de données parent | dct:isPartOf | |

## Millésime -> dcat:Distribution

| nom DiDo   |  type  | description               | nom DCAT   | transformation                                  | commentaire |
|------------|--------|---------------------------|------------|-------------------------------------------------|-------------|
| millesime  | string | Le millésime du fichier - format YYYY-MM | @id | URI https://dido.geoapi.fr/id/millesimes/{rid}/{m} |
| title      | string | Titre du fichier | dct:title | |
| date_diffusion | date-time | Date de diffusion du millesime du fichier | dct:issued | |
| rows | integer | Nombre de lignes dans le fichier | | |
| columns | array | Liste des colonnes du fichier | ct:conformsTo | Structuration de la liste des colonnes comme schéma JSON | **Problème d'encodage des unités !** |
| extendedFilters | array | Liste des filtres étendus du fichier | | |
| geoFields | array | Liste des champs disposant d'une géométrie dans le fichier | | Voir la possibilité d'intégrer l'info dans le schéma JSON |
