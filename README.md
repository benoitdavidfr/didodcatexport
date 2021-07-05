# Conception d'un export DCAT du catalogue de DiDo
DiDo est un outil de diffusion des données du service des statistiques (SDES) du MTE.
Un site école est documenté sur https://datahub-ecole.recette.cloud/api-diffusion/v1/apidoc.html
Le site de diffusion officiel devrait ouvrir prochainement sur internet.

Le présent projet consiste à concevoir et prototyper un export DCAT du catalogue des données de DiDo.  
La [correspondance en DCAT des classes d'objets DiDo est définie ici](mapping.md).  

DCAT est une ontologie du web des données fondée sur l'identification des ressources par des URI.
Un export DCAT impose donc de définir un URI pour chaque ressource d'une des classes définies dans la correspondance ci-dessus (sauf rdfs:Literal).  
Dans le prototype les URI des objets DiDo sont de la forme:

  - https://dido.geoapi.fr/id/catalog pour le catalogue (dcat:Catalog)
  - https://dido.geoapi.fr/id/datasets/{id} pour le jeu de données DiDo {id} (dcat:Dataset)
  - https://dido.geoapi.fr/id/attachments/{rid} pour les fichiers annexe {rid} (foaf:Document)
  - https://dido.geoapi.fr/id/datafiles/{rid} pour le fichier de données {rid} (dcat:Dataset)
  - https://dido.geoapi.fr/id/millesimes/{rid}/{m} pour le millésime {m} du fichier de données {rid} (dcat:Distribution)
  - https://dido.geoapi.fr/id/organizations/{id} pour l'organisation {id} (foaf:Organzation)
  - https://dido.geoapi.fr/id/themes pour les thèmes DiDo (skos:ConceptScheme)
  - https://dido.geoapi.fr/id/themes/{id} pour le thème DiDo {id} (skos:Concept)
  - https://dido.geoapi.fr/id/json-schema/{rid}/{m} pour le schéma JSON du millésime {m} du fichier de données {rid} (foaf:Document)

L'export DCAT est disponible en JSON-LD à l'adresse https://dido.geoapi.fr/v1/dcatexport.jsonld  


