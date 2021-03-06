# Export DCAT du catalogue de DiDo
DiDo est un outil de diffusion des données du service des statistiques (SDES) du MTE.
Sa documentation est accessible sur https://data.statistiques.developpement-durable.gouv.fr/dido/api/v1/apidoc.html.

Le présent projet consiste à concevoir et prototyper un export [DCAT](https://www.w3.org/TR/vocab-dcat-2/)
du catalogue des données de DiDo.  
Les [correspondances en DCAT des classes, des propriétés et des valeurs possibles définies dans DiDo sont définies ici](mapping.md).  

Les principes suivants ont été retenus pour l'exposition DCAT:

  - L'export DCAT est exposé à l'URL `https://dido.geoapi.fr/v1/dcatexport.{fmt}?page={page}&page_size={page_size}`
    - où:
      - {fmt} est le format souhaité obligatoire qui peut être:
        - jsonld pour [JSON-LD](https://www.w3.org/TR/json-ld11/)
        - ttl pour [Turtle](https://www.w3.org/TR/turtle/)
        - rdf pour [RDF/XML](https://www.w3.org/TR/rdf-syntax-grammar/)
        - html pour un affichage en HTML de l'export Turtle
      - {page} est le numéro de page à partir de 1, par défaut vaut 1,
      - {page_size} est:
        - soit le nombre de dcat:Dataset par sous-objet dcat:Catalog, par défaut vaut 10,
        - soit la valeur 'all' indiquant une absence de pagination.
    - exemples:
      - https://dido.geoapi.fr/v1/dcatexport.jsonld
      - https://dido.geoapi.fr/v1/dcatexport.html?page=2&page_size=20
  - Le contexte JSON-LD de l'export est exposé à l'URL `https://dido.geoapi.fr/v1/dcatcontext.jsonld`
  - La ressource JSON-LD correspondant au catalogue (dcat:Catalog) peut contenir de nombreux jeux de données
    et son export est donc paginé selon les principes d'une [Collection Hydra](https://www.hydra-cg.com/spec/latest/core/)
    identiques à ceux utilisés dans l'export DCAT de https://data.gouv.fr/ à l'URL https://www.data.gouv.fr/catalog.jsonld.
  - La page contenant un sous-objet dcat:Catalog contient aussi tous les Dataset et autres objets liés qui n'ont pas 
    été fournis dans les pages précédentes.
  - Chaque ressource de l'export est identifiée par un URI conforme aux modèles ci-dessous sauf les fichiers
    annexes (Attachment) dont l'URI est l'URL de téléchargement du fichier.
  - L'URL de téléchargement associé à chaque millésime exposé dans l'export est celui fourni par DiDo pour le format CSV
    avec pour chaque colonne son nom, sa description et son unité.
  - Outre le fichier CSV, à chaque millésime est associé un schéma JSON qui fournit la liste des champs du fichier CSV.
  - Pour chaque référentiel et chaque nomenclature, un dcat:Dataset est défini ainsi que des dcat:Distribution
    correspondants à un ou plusieurs formats de téléchargement (CSV, JSON, GéoJSON) ;
    un schéma JSON est aussi associé à chaque référentiel et à chaque nomenclature.
  - Les données exposées par le prototype sont celles exposées sur le site école indiqué ci-dessus ;
    en amont de l'export DCAT un téléchargement des méta-données DiDo est effectué à partir de ce site.


DCAT étant une ontologie du web des données, les ressources sont identifiées par des URI
et il est donc nécessaire de définir des URI pour chaque ressource apparaissant dans l'export.
La plupart des URL fournies par DiDo ne peuvent pas être utilisés comme URI
car ils contiennent le no de version de l'API et ne sont donc pas stables.  
Ainsi des URI ont été définis dans le prototype et sont de la forme :

  - https://dido.geoapi.fr/id/catalog pour le catalogue (dcat:Catalog)
  - https://dido.geoapi.fr/id/datasets/{id} pour le jeu de données DiDo {id} (dcat:Dataset)
  - https://dido.geoapi.fr/id/datafiles/{rid} pour le fichier de données {rid} (dcat:Dataset)
  - https://dido.geoapi.fr/id/datafiles/{rid}/millesimes/{m}/formats/{format} pour le millésime {m} du fichier de données {rid}
    dans le format {format} (dcat:Distribution)
  - https://dido.geoapi.fr/id/datafiles/{rid}/millesimes/{m}/json-schema pour le schéma JSON des éléments du millésime {m}
    du fichier de données {rid} (foaf:Document)
  - https://dido.geoapi.fr/id/organizations/{id} pour l'organisation {id} (foaf:Agent)
  - https://dido.geoapi.fr/id/organizations/SDES pour l'organisation SDES non définie dans DiDo (foaf:Agent)
  - https://dido.geoapi.fr/id/themes pour le vocabulaire des thèmes DiDo (skos:ConceptScheme)
  - https://dido.geoapi.fr/id/themes/{id} pour le thème DiDo {id} (skos:Concept)
  - https://dido.geoapi.fr/id/tags pour les mots-clés DiDo (skos:ConceptScheme)
  - https://dido.geoapi.fr/id/tags/{id} pour le mot-clé DiDo {id} (skos:Concept)
  - https://dido.geoapi.fr/id/referentiels/{id} pour le référentiel DiDo {id} (dcat:Dataset)
  - https://dido.geoapi.fr/id/referentiels/{id}/formats/{format} pour le référentiel DiDo {id} dans le format {format}
    (dcat:Distribution)
  - https://dido.geoapi.fr/id/referentiels/{id}/json-schema pour le schéma JSON des éléments du référentiel {id} (foaf:Document)
  - https://dido.geoapi.fr/id/nomenclatures/{id} pour la nomenclature DiDo {id} (dcat:Dataset)
  - https://dido.geoapi.fr/id/nomenclatures/{id}/formats/{format} pour la nomenclature DiDo {id} dans le format {format}
    (dcat:Distribution)
  - https://dido.geoapi.fr/id/nomenclatures/{id}/json-schema pour le schéma JSON des éléments de la nomenclature {id} (foaf:Document)

Chaque URI est déréférencable et renvoit la description de la ressource en JSON-LD à l'exception
des URI de foaf:Document, dc:LicenseDocument et dc:Standard qui renvoient vers un document (JSON, HTML, PDF, CSV, ...)
exposé en HTTP.

L'export DCAT est finalement testé par le validateur DCAT-AP exposé sur https://data.europa.eu/mqa/shacl-validator-ui/.
