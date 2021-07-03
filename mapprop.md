# Correspondance entre propriétés et valeurs

## Organization -> foaf:Organization

| nom DiDo   |  type  | description               | nom DCAT   | transformation                                               |
|------------|--------|---------------------------|------------|--------------------------------------------------------------|
| id         | string | Identifiant du producteur | @id        | URI https://dido.geoapi.fr/id/organizations/{id}             |
| title      | string | Nom du producteur         | foaf:name  |                                                              |
| acronym    | string | Acronyme du producteur    | foaf:nick  |                                                              |
| description| string | Description du producteur | rdfs:comment |                                                            |

## Dataset -> dcat:Dataset

| nom DiDo   |  type  | description               | nom DCAT   | transformation                                  | commentaire |
|------------|--------|---------------------------|------------|-------------------------------------------------|-------------|
| id         | string | Identifiant du jeu de données | @id    | URI https://dido.geoapi.fr/id/datasets/{id} |
| title      | string | Titre du jeu de données | dct:title | |
| description | string | Description du jeu de données | dct:description |  |
| organization | string | infos sur le producteur du JD | dct:publisher | URI https://dido.geoapi.fr/id/organizations/{id} |
| topic      | string | Thème du jeu de données | dcat:theme | URI https://dido.geoapi.fr/id/themes/{id} |
| tags       | string | Liste des mot-clés du jeu de données | dcat:keyword | |
| license    | string | Licence sous laquelle est publiée le JD | dct:license | **A PRECISER** |
| frequency  | string | Fréquence d'actualisation du jeu de données | dct:Frequency | URI dans http://publications.europa.eu/resource/authority/frequency selon correspondance définie | la valeur 'punctual' n'a pas de correspondance, voir dans quels cas elle est utilisée |
| frequency_date | date-time | Prochaine date d'actualisation du jeu de données | **Notion absente** | |
| spatial/granularity | string | Granularité du jeu de données | **Notion absente** | |
| spatial/zones | string | Liste de zones géographiques du jeu de données (correspond à un identifiant du référentiel geozone) | dct:spatial | **A PRECISER** |
| temporal_coverage/start | date - format YYYY-MM-DD | Date de début de la couverture temporelle | dct:temporal/startDate | |
| temporal_coverage/end | date - format YYYY-MM-DD | Date de fin de la couverture temporelle | dct:temporal/endDate | |
| caution    | string | Mise en garde concernant le JD | **A PRECISER** |
| attachments | [Attachment] | La liste des fichiers descriptifs | foaf:page | URI https://dido.geoapi.fr/id/attachments/{rid} |
| created_at | date-time | date de création du jeu de données | dct:created | |
|            |   |   | dct:issued | | Il semble utile de rajouter une date de publication qui n'est pas dans DiDo. Voir si la date de création convient. Attention aux cas d'embargo. |
| last_modified | date-time | Date de dernière modification du jeu de données | dct:modified |
| datafiles  | [Datafile] | Liste des fichiers de données | dct:hasPart | URI https://dido.geoapi.fr/id/datafiles/{id} |

### Frequency -> http://publications.europa.eu/resource/authority/frequency

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

## Attachment -> foaf:Document

| nom DiDo   |  type  | description               | nom DCAT   | transformation                                  | commentaire |
|------------|--------|---------------------------|------------|-------------------------------------------------|-------------|
| rid        | uuid   | Identifiant du fichier descriptif | @id | URI https://dido.geoapi.fr/id/attachments/{rid} |
| title      | string | Titre du fichier descriptif | dct:title | |
| description | string | Description du fichier descriptif | dct:description |  |
| published | date-time | Date de publication du fichier descriptif | dct:issued |  |
| url | string | Url pour accéder au fichier descriptif | **??** | | Je ne vois pas quelle propriété foaf doit être utilisée ! |
| created_at | date-time | Date de création du fichier descriptif  | dct:created | | **Attention fichier Swagger erroné** |
| last_modified | date-time | Date de dernière modification du fichier descriptif | dct:modified | | **Attention fichier Swagger erroné** |


# JSON
definitions:
  datafileFull:
    title: datafileFull
    description: 'Schéma json d''un fichier de données complet (avec son jeu de données parent)'
    type: object
    allOf:
      - $ref: '#/definitions/datafileSimple'
      - type: object
        required:
          - dataset
        properties:
          dataset:
            $ref: '#/definitions/datasetSimple'
  datafileSimple:
    title: datafileSimple
    description: Schéma JSON d'un fichier de données simple (sans son jeu de données parent)
    type: object
    required:
      - rid
      - title
      - description
      - published
      - weburl
      - created_at
      - millesimes
    properties:
      rid:
        description: 'Identifiant du fichier de données'
        type: string
        format: uuid
        example: 2f48a6cd-b147-4750-aa70-990a5c17f536
      title:
        description: 'Titre du fichier de données'
        type: string
        example: 'Le titre de mon fichier de données'
      description:
        description: 'Description du fichier de données'
        type: string
        example: 'Ce fichier de données.......'
      published:
        description: 'Date de publication du fichier de données - format iso 8601'
        type: string
        format: date-time
        example: '2017-10-27T22:00:00.000Z'
      temporal_coverage:
        description: 'Couverture temporelle du fichier de données'
        type: object
        required:
          - start
          - end
        properties:
          start:
            description: 'Date de début de la couverture temporelle du fichier de données - format YYYY-MM-DD'
            type: string
            example: '2017-01-01'
          end:
            description: 'Date de fin de la couverture temporelle du fichier de données - format YYYY-MM-DD'
            type: string
            example: '2017-06-30'
      legal_notice:
        description: 'Notice légale concernant le fichier de données'
        type: string
        example: 'Ces données .....'
      weburl:
        description: 'Url pour accéder à l''interface de visualisation du fichier de données'
        type: string
        example: 'https://datahub-ecole.recette.cloud/widget-diffusion//datafile/2f48a6cd-b147-4750-aa70-990a5c17f536'
      millesimes:
        description: 'Informations sur les millésimes du fichier de données'
        type: array
        items:
          description: 'Schéma json des informations sur un millésime'
          $ref: '#/definitions/millesime'
      created_at:
        description: 'date de création du fichier de données - format iso 8601'
        type: string
        format: date-time
        example: '2018-01-26T21:58:34.190Z'
      last_modified:
        description: 'Date de dernière modification du fichier de données - format iso 8601'
        type: string
        format: date-time
        example: '2018-01-26T21:58:34.190Z'
  millesime:
    description: 'Schéma json des informations sur un millésime'
    type: object
    required:
      - millesime
      - date_diffusion
    properties:
      millesime:
        description: 'Le millésime du fichier de données - format YYYY-MM'
        type: string
        example: 2017-10
      date_diffusion:
        description: 'Date de diffusion du millesime du fichier de données - format iso 8601'
        type: string
        format: date-time
        example: '2017-10-27T22:00:00.000Z'
      rows:
        description: 'Nombre de lignes dans le fichier de données'
        type: integer
        example: 2548
      columns:
        description: 'Liste des colonnes du fichier de données'
        type: array
        items:
          description: 'Détail d''une colonne de fichier de données'
          type: object
          required:
            - name
            - description
            - unit
            - filters
          properties:
            name:
              description: 'Nom de la colonne'
              type: string
              example: COLONNE_N
            description:
              description: 'Description de la colonne'
              type: string
              example: 'Description de la COLONNE_N.....'
            unit:
              description: 'Unité de la colonne'
              type: string
              example: t
            filters:
              description: 'Liste des filtres disponibles pour cette colonne. Dépend de la colonne.'
              type: array
              items:
                type: string
              example:
                - eq
                - ne
                - gt
                - gte
                - lt
                - lte
                - in
                - nin
                - startsWith
                - endsWith
      extendedFilters:
        description: 'Liste des filtres étendus du fichier de données'
        type: array
        items:
          description: 'Détail d''un filtre étendu de fichier de données'
          type: object
          required:
            - name
            - columns
            - filters
          properties:
            name:
              description: 'Nom du filtre étendu'
              type: string
              example: LOCATION
            columns:
              description: 'La liste des colonnes sur lesquelles s''appliquent le filtre étendu'
              type: array
              items:
                type: string
                example:
                  - COMMUNE_LIBELLE
                  - COMMUNE_CODE
            filters:
              description: 'Liste des type de filtres disponibles pour ce filtre étendu.'
              type: array
              items:
                type: string
              example:
                - withinCogZones
                - withinGeometry
      geoFields:
        description: 'Liste des champs disposant d''une géométrie dans le fichier de données'
        type: array
        items:
          type: string
          description: 'Un champ'
          example: LOCATION
