# Correspondances de DiDo en DCAT

mise à jour: 8/7/2021 10h35

## Correspondances entre classes d'objets

L'export DCAT nécessite tout d'abord de définir une correspondance entre les classes du catalogue de DiDo
vers celles définies dans DCAT.

Le catalogue de DiDo définit les classes d'objets suivantes:

  - jeu de données (dataset),
  - fichiers annexes (attachments)
  - fichiers de données (datafile)
  - organisation (organization)
  - millésime
  - theme
  - mot-clé
  - référentiel
  - nomenclature

Le standard DCAT (https://www.w3.org/TR/vocab-dcat-2/) est fondé notamment sur les classes suivantes:

  - [dcat:Catalog](https://www.w3.org/TR/vocab-dcat-2/#Class:Catalog)
  - [dcat:Dataset](https://www.w3.org/TR/vocab-dcat-2/#Class:Dataset)
  - [dcat:Distribution](https://www.w3.org/TR/vocab-dcat-2/#Class:Distribution)
  - [dcat:DataService](https://www.w3.org/TR/vocab-dcat-2/#Class:DataService)
  - [foaf:Agent](http://xmlns.com/foaf/spec/#term_Agent)
  - [foaf:Document](http://xmlns.com/foaf/spec/#term_Document)
  - [skos:Concept](https://www.w3.org/TR/skos-primer/#secconcept)
    et [skos:ConceptScheme](https://www.w3.org/TR/skos-primer/#secscheme)

La correspondance choisie pour les classes est la suivante:

| classe DiDo            | classe RDFS     | commentaire | 
|------------------------|-----------------|-------------|
|                        | dcat:Catalog    | Un catalogue DCAT est défini et comprend tous les jeux de données et les fichiers de données ; cette ressource n'apparait pas explicitement dans DiDo. |
| jeu de données         | dcat:Dataset    | Un jeu de données DiDo est composé de fichiers de données au moyen des propriétés dct:hasPart/dct:isPartOf conformément à la recommandations définie dans https://joinup.ec.europa.eu/release/dcat-ap-how-model-dataset-series. |
| fichier annexes        | foaf:Document   | Un fichier annexe est référencé depuis le jeu de données au travers d'une propriété foaf:page ; son URI sera l'URL de téléchargement défini par DiDo. |
| fichier de données     | dcat:Dataset    | Un fichier de données DiDo fait partie d'un jeu de données DiDo ; il définit une propriété dct:conformsTo vers un fichier contenant un schéma JSON du fichier de données, lui-même défini comme foaf:Document. |
| millesime              | dcat:Distribution  |
| organisation           | foaf:Agent      | DCAT-AP exige que l'on utilise la classe foaf:Agent et pas la classe foaf:Organization |
| thème                  | skos:Concept    | La correspondance de ces thèmes vers le vocabulaire data-theme est définie ci-dessous |
| vocabulaire des thèmes | skos:ConceptScheme | L'ensemble des thémes DiDo est structuré dans un skos:ConceptScheme |
| mot-clé                | rdfs:Literal    | Un mot-clé DiDo sera représenté par un rdfs:Literal lié par la propriété dcat:keyword |
| référentiel            | dcat:Dataset    | Un référentiel expose différentes distributions correspondant aux différents formats |
| nomenclature           | dcat:Dataset    | Une nomenclature expose différentes distributions correspondant aux différents formats |

Dans un premier temps, il n'est pas envisagé de représenter les API DiDo comme dcat:DataService car cette dernière classe est spécifique de DCAT v2 et encore peu utilisée.
La solution sera d'indiquer dans les dcat:Distribution correspondant à un millésime un lien vers un fichier CSV généré par DiDo.

La suite du document décrit pour chaque classe la correspondance des propriétés et dans certains cas des valeurs possibles.

## Correspondance des propriétés ainsi que de certaines valeurs

### Propriétés de Producteur (Organization) -> foaf:Agent

| nom DiDo   |  type  | description               | nom DCAT   | transformation |
|------------|--------|---------------------------|------------|----------------|
| id         | string | Identifiant du producteur | @id        | URI https://dido.geoapi.fr/id/organizations/{id} |
|            |        |                           | @type      | foaf:Agent |
| title      | string | Nom du producteur         | foaf:name  |
| acronym    | string | Acronyme du producteur    | foaf:nick  |
| description| string | Description du producteur | rdfs:comment |

Note:

  - il manque à DiDo la notion de point de contact avec notamment une adresse mail et un numéro de téléphone pour contacter
    une personne qui peut répondre à des questions sur le jeu de données.
    Etant donné l'organisation du SDES, le point de contact pourrait être l'adresse électronique du bureau producteur.

### Propriétés de Jeu de données (Dataset) -> dcat:Dataset

| nom DiDo   |  type  | description               | nom DCAT   | transformation                                  | commentaire |
|------------|--------|---------------------------|------------|-------------------------------------------------|-------------|
| id         | string | Identifiant du jeu        | @id        | URI https://dido.geoapi.fr/id/datasets/{id}     |
|            |        |                           | @type      | ['Dataset', 'http://inspire.ec.europa.eu/metadata-codelist/ResourceType/series'] | On utilise l'URI Inspire de series qui correspond à un ensemble de jeux de données |
| id         | string | Identifiant du jeu        | identifier | |
| title      | string | Titre du jeu de données   | dct:title | |
| description | string | Description du jeu de données | dct:description | |
| organization | string | Infos sur le producteur du JD | dct:publisher | URI https://dido.geoapi.fr/id/organizations/{id} |
| topic      | string | Thème du jeu de données | dcat:theme | URI https://dido.geoapi.fr/id/themes/{id} + mapping des themes DiDo vers le voc. data-theme |
| tags       | string | Liste des mot-clés du jeu de données | dcat:keyword | |
| license    | string | Licence sous laquelle est publiée le JD | dct:license | Voir ci-dessous la correspondance des valeurs |
| frequency  | string | Fréquence d'actualisation du jeu de données | dct:accrualPeriodicity | URI dans http://publications.europa.eu/resource/authority/frequency selon correspondance définie ci-dessous |
| frequency_date | date-time | Prochaine date d'actualisation du jeu de données | | **Notion absente dans DCAT** |
| spatial/granularity | string | Granularité du jeu de données | | **Notion absente dans DCAT** |
| spatial/zones | string | Liste de zones géographiques du jeu de données (correspond à un identifiant du référentiel geozone) | dct:spatial | Voir ci-dessous la correspondance des valeurs |
| temporal_coverage/start | date - format YYYY-MM-DD | Date de début de la couverture temporelle | dct:temporal/startDate | |
| temporal_coverage/end | date - format YYYY-MM-DD | Date de fin de la couverture temporelle | dct:temporal/endDate | |
| caution    | string | Mise en garde concernant le JD | **A PRECISER** Utiliser éventuellement dct:rights |
| attachments | [Attachment] | La liste des fichiers descriptifs | foaf:page | comme URI l'URL DiDo de téléchargement du fichier |
| created_at | date-time | date de création du jeu de données | dct:created | |
| last_modified | date-time | Date de dernière modification du jeu de données | dct:modified |
| datafiles  | [Datafile] | Liste des fichiers de données | dct:hasPart | liste d'URI https://dido.geoapi.fr/id/datafiles/{id} |

### Valeurs Topic -> http://publications.europa.eu/resource/authority/data-theme
Correspondance des thèmes DiDo vers un thème de data-theme.  

| thème DiDo    |  URI                                                             | commentaire |
|---------------|------------------------------------------------------------------|-------------|
| Environnement | http://publications.europa.eu/resource/authority/data-theme/ENVI |
| Énergie       | http://publications.europa.eu/resource/authority/data-theme/ENER |
| Transports    | http://publications.europa.eu/resource/authority/data-theme/TRAN |
| Logement      | http://publications.europa.eu/resource/authority/data-theme/SOCI | Population et société |
| Changement climatique |  http://publications.europa.eu/resource/authority/data-theme/ENVI | Environnement |

### Valeurs pour le champ licence
Correspondance en valeurs pour le champ licence vers des URI.  
Ces URI sont déclarées comme des objets de la classe dct:LicenseDocument.

| thème DiDo    |  URI                                                             | commentaire                             |
|---------------|------------------------------------------------------------------|-----------------------------------------|
| fr-lo         | https://www.etalab.gouv.fr/licence-ouverte-open-licence          | URL utilisée notamment sur data.gouv.fr |

### Valeurs Frequency -> http://publications.europa.eu/resource/authority/frequency
Correspondance des fréquences (valeurs possibles du champ frequency) vers un concept du vocabulaire
http://publications.europa.eu/resource/authority/frequency  
Ces URI sont déclarées comme des objets de la classe dct:Frequency.

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


### Valeurs Géozones -> URI

Les valeurs de [GéoZones](https://www.data.gouv.fr/fr/datasets/geozones/) ne peuvent pas être utilisées
car elles ne sont pas définies par des URI.  
L'idée générale est d'utiliser pour les différents territoires l'URI défini par la Commission EU fondé sur les codes ISO 3166-1
et exigé dans DCAT-AP.  
Cependant pour la notion de *France*, il existe 3 extensions géographiques distinctes qu'il est utile de distinguer :

  - la métrople
  - la métropole plus les 5 DROM
  - l'ensemble du territoire y compris l'ensemble de l'outre-mer

En utilisant l'URI http://publications.europa.eu/resource/authority/country/FRA on perdrait cette précision.
Le choix a donc été fait d'utiliser les URI de l'INSEE pour la métropole et métropole+5 DROM :

  - http://id.insee.fr/geo/territoireFrancais/franceMetropolitaine pour la France métropolitaine
  - http://id.insee.fr/geo/pays/france pour la France métropolitaine plus les 5 DROM.

Pour l'outre-mer on utilisera les URI suivants :

  - http://publications.europa.eu/resource/authority/country/GLP pour la Guadeloupe,
  - http://publications.europa.eu/resource/authority/country/MTQ pour la Martinique,
  - http://publications.europa.eu/resource/authority/country/GUF pour la Guyane,
  - http://publications.europa.eu/resource/authority/country/REU pour la Réunion,
  - http://publications.europa.eu/resource/authority/country/MYT pour Mayotte,
  - http://publications.europa.eu/resource/authority/country/BLM pour Saint-Barthélémy,
  - http://publications.europa.eu/resource/authority/country/MAF pour Saint-Martin,
  - http://publications.europa.eu/resource/authority/country/WLF pour Wallis-et-Futuna,
  - http://publications.europa.eu/resource/authority/country/PYF pour la Polynésie Française,
  - http://publications.europa.eu/resource/authority/country/NCL pour la Nouvelle Calédonie,
  - http://publications.europa.eu/resource/authority/country/FQ0 pour les Terres australes et antarctiques françaises,
  - http://publications.europa.eu/resource/authority/country/CPT pour l'île de Clipperton.

L'union géographique de différents territoires sera définie par un ensemble constitué des URI de chacun des territoires.

Ainsi la correspondance des valeurs trouvées dans DiDo est la suivante :

| Géozone       |  URI                                                             | commentaire |
|---------------|------------------------------------------------------------------|-------------|
| country:fr    | http://id.insee.fr/geo/pays/france | Il s'agit de la métrople plus les 5 DROM  |
| country-subset:fr :metro | http://id.insee.fr/geo/territoireFrancais/franceMetropolitaine | Il s'agit de la métrople |
| country-subset:fr :drom  | [http://publications.europa.eu/resource/authority/country/GLP, http://publications.europa.eu/resource/authority/country/MTQ, http://publications.europa.eu/resource/authority/country/GUF, http://publications.europa.eu/resource/authority/country/REU, http://publications.europa.eu/resource/authority/country/MYT], | Il s'agit des 5 DROM  |


### Fichier descriptif (Attachment) -> foaf:Document

| nom DiDo   |  type  | description               | nom DCAT   | transformation                                  | commentaire |
|------------|--------|---------------------------|------------|-------------------------------------------------|-------------|
| url        | string | Url pour accéder au fichier | @id      |                         | Utilisation comme URI de l'URL DiDo |
|            |        |                           | @type      | foaf:Document |
| title      | string | Titre du fichier          | dct:title  |
| description | string | Description du fichier   | dct:description |
| published | date-time | Date de publication du fichier | dct:issued |
| created_at | date-time | Date de création du fichier | dct:created |
| last_modified | date-time | Date de dernière modification du fichier | dct:modified |

### Fichier de données (Datafile) -> dcat:Dataset

| nom DiDo   |  type  | description               | nom DCAT   | transformation                                  | commentaire |
|------------|--------|---------------------------|------------|-------------------------------------------------|-------------|
| rid        | uuid   | Identifiant du fichier    | @id        | URI https://dido.geoapi.fr/id/datafiles/{rid}   |
|            |        |                           | @type      | dcat:Dataset |
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
| millesime  | string | Le millésime du fichier - format YYYY-MM | @id | URI https://dido.geoapi.fr/id/datafiles/{rid}/millesimes/{m} |
|            |        |                           | @type      | dcat:Distribution |
| title      | string | Titre du fichier          | dct:title  |
| date_diffusion | date-time | Date de diffusion du millesime du fichier | dct:issued | 
| rows | integer | Nombre de lignes dans le fichier | | **Non repris** |
| columns | array | Liste des colonnes du fichier | dct:conformsTo | Structuration de la liste des colonnes comme schéma JSON | **Problème d'encodage des unités !** |
| extendedFilters | array | Liste des filtres étendus du fichier | | |
| geoFields | array | Liste des champs disposant d'une géométrie dans le fichier | | Voir la possibilité d'intégrer l'info dans le schéma JSON |
|            |        |                           | accessURL  | construction de l'URL d'obtention au fichier CSV |
|            |        |                           | downloadURL| construction de l'URL d'obtention au fichier CSV |
|            |        |                           | mediaType  | {"@id": "https://www.iana.org/assignments/media-types/text/csv", "@type": "dct:MediaType"} |

### Référentiel/Nomenclature -> dcat:Dataset/dcat:Distribution
Ces propriétés sont principalement issues des informations disponibles dans le Swagger.

| nom DiDo   |  type  | description               | nom DCAT   | transformation                                  | commentaire |
|------------|--------|---------------------------|------------|-------------------------------------------------|-------------|
|            |        |                           | @id        | URI utilisant un nom issu du Swagger de la forme 'https://dido.geoapi.fr/id/(referentiels|nomenclatures)/{id}' |
|            |        |                           | @type      | "dcat:Dataset"                                  |
|            |        |                           | title      | titre proposé                                   |
|            |        |                           | description| description proposée                            |
|            |        |                           | theme      | un theme data-theme et un Dido lorsque cela a du sens |
|            |        |                           | keyword    | "Référentiel DiDo" ou "Nomenclature DiDo"       |
|            |        |                           | license    | 'https://www.etalab.gouv.fr/licence-ouverte-open-licence' |
|            |        |                           | distribution | Liste de distributions, une par format exposé |

| nom DiDo   |  type  | description               | nom DCAT   | transformation                                  | commentaire |
|------------|--------|---------------------------|------------|-------------------------------------------------|-------------|
|            |        |                           | @id        | URI de la forme https://dido.geoapi.fr/id/(referentiels\|nomenclatures)/{id}/formats/{format} |
|            |        |                           | @type      | "dcat:Distribution"                             |
|            |        |                           | title      | titre proposé                                   |
|            |        |                           | description| description proposée                            |
|            |        |                           | license    | 'https://www.etalab.gouv.fr/licence-ouverte-open-licence' |
|            |        |                           | mediaType  | URI correspondant au format                     |
|            |        |                           | conformsTo | URI correspondant au schéma JSON de la forme https://dido.geoapi.fr/id/(referentiels|nomenclatures)/{id}/json-schema | Le schéma est défini à partir du Swagger |
|            |        |                           | accessURL  | URL correspondant au téléchargement dans le format |
|            |        |                           | downloadURL| URL correspondant au téléchargement dans le format |

