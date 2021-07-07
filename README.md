# Export DCAT du catalogue de DiDo
DiDo est un outil de diffusion des données du service des statistiques (SDES) du MTE.
Un site école est documenté sur https://datahub-ecole.recette.cloud/api-diffusion/v1/apidoc.html
Le site de diffusion officiel devrait ouvrir prochainement sur internet.

Le présent projet consiste à concevoir et prototyper un export DCAT du catalogue des données de DiDo.  
La [correspondance en DCAT des classes, des propriétés et des valeurs possibles définies dans DiDo est définie ici](mapping.md).  

Les principes suivants ont été retenus pour l'exposition DCAT:

  - En amont de l'export DCAT un téléchargement des méta-données DiDo est effectué à partir du site école indiqué ci-dessus.
  - L'export DCAT est exposé en JSON-LD à l'URL https://dido.geoapi.fr/v1/dcatexport.jsonld?page={page}&page_size={page_size} ;
    son contexte  est exposé à l'URL https://dido.geoapi.fr/v1/dcatcontext.jsonld
  - Chaque objet DiDo est identifié par un URI conforme aux modèles ci-dessous.
  - La ressource JSON-LD correspondant au catalogue (dcat:Catalog) peut contenir de nombreux jeux de données
    et son export est donc paginée selon les principes d'une [Collection Hydra](https://www.hydra-cg.com/spec/latest/core/)
    identiques à ceux utilisés dans l'export DCAT de https://data.gouv.fr/ à l'URL https://www.data.gouv.fr/catalog.jsonld.
  - Le paramètre page_size de la requête d'exposition correspond au nbre de dcat:Dataset par sous-objet dcat:Catalog
  - La page contenant un sous-objet dcat:Catalog contient aussi tous les Dataset et autres objets liés qui n'ont pas 
    été fournis dans les pages précédentes.
  - Les URL de téléchargement des données exposés dans l'export sont ceux fournis par DiDo pour le format CSV.
  - A chaque millésime est associé un fichier CSV et un schéma JSON qui fournit les champs du fichier CSV.
  - Chaque fichier annexe (Attachment) correspond à un URI dont le déréférencement produit le téléchargement de ce fichier.

DCAT étant une ontologie du web des données fondée sur l'identification des ressources par des URI,
il est nécessaire de définir des URI pour chaque ressource apparaissant dans l'export.  
Dans le prototype les URI des objets DiDo sont de la forme:

  - https://dido.geoapi.fr/id/catalog pour le catalogue (dcat:Catalog)
  - https://dido.geoapi.fr/id/datasets/{id} pour le jeu de données DiDo {id} (dcat:Dataset)
  - https://dido.geoapi.fr/id/attachments/{rid} pour le fichier annexe {rid} (foaf:Document)
  - https://dido.geoapi.fr/id/datafiles/{rid} pour le fichier de données {rid} (dcat:Dataset)
  - https://dido.geoapi.fr/id/millesimes/{rid}/{m} pour le millésime {m} du fichier de données {rid} (dcat:Distribution)
  - https://dido.geoapi.fr/id/json-schema/{rid}/{m} pour le schéma JSON du millésime {m} du fichier de données {rid} (foaf:Document)
  - https://dido.geoapi.fr/id/organizations/{id} pour l'organisation {id} (foaf:Agent)
  - https://dido.geoapi.fr/id/themes pour les thèmes DiDo (skos:ConceptScheme)
  - https://dido.geoapi.fr/id/themes/{id} pour le thème DiDo {id} (skos:Concept)

L'export DCAT est disponible en JSON-LD à l'adresse https://dido.geoapi.fr/v1/dcatexport.jsonld  


