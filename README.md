# Conception d'un export DCAT du catalogue de DiDo
DiDo est un outil de diffusion des données du service des statistiques (SDES) du MTE.
Un site école est documenté sur https://datahub-ecole.recette.cloud/api-diffusion/v1/apidoc.html
Le site de diffusion officiel devrait ouvrir prochainement sur internet.

Le présent projet consiste à concevoir et prototyper un export DCAT du catalogue des données de DiDo.  
La [correspondance en DCAT des classes d'objets DiDo est définie ici](mapping.md).  

DCAT est une ontologie du web des données fondée sur l'identification des ressources par des URI.
Un export DCAT impose donc de définir un URI pour chaque ressource d'une des classes définies dans la correspondance ci-dessus (sauf rdfs:Literal).  
Dans le prototype les URI des objets DiDo sont de la forme:

  - https://dido.geoapi.fr/id/catalog pour le catalogue
  - https://dido.geoapi.fr/id/datasets/{id} pour les jeux de données DiDo
  - https://dido.geoapi.fr/id/attachments/{rid} pour les fichiers annexes
  - https://dido.geoapi.fr/id/datafiles/{rid} pour les fichiers de données
  - https://dido.geoapi.fr/id/millesimes/{rid}/{m} pour les millésimes
  - https://dido.geoapi.fr/id/organizations/{id} pour les organisations
  - https://dido.geoapi.fr/id/themes/{id} pour les thèmes DiDo
  - https://dido.geoapi.fr/id/geozones/{id} pour les géozones

Le prototype sera disponible à l'adresse https://dido.geoapi.fr/v1/dcatexport.jsonld  


