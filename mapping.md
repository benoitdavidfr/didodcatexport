## Correspondance de DiDo vers DCAT

mise à jour: 2/7/2021 17h33

L'export DCAT nécessite tout d'abord de définir une correspondance entre les classes du catalogue de DiDo vers celles définies dans DCAT.
Le catalogue de DiDo définit les classes d'objets suivantes:
  - jeu de données (dataset),
  - fichiers annexes (attachments)
  - fichiers de données (datafile)
  - organisation (organization)
  - millesime
  - theme
  - mot-clé

Le standard DCAT (https://www.w3.org/TR/vocab-dcat-2/) est fondé sur les classes suivantes:
  - dcat:Dataset
  - dcat:Distribution
  - dcat:DataService
  - foaf:Organization
  - skos:Concept et skos:ConceptScheme

La correspondance proposée est la suivante:
  - jeu de données -> dcat:Dataset
  - fichiers annexes -> foaf:Document
  - fichiers de données -> dcat:Dataset
  - millesime -> dcat:Distribution
  - organisation -> foaf:Organization
  - theme -> skos:Concept
  - mot-clé -> rdfs:Literal

Notes:
  - Un jeu de données DiDo sera représenté en DCAT par un dcat:Dataset qui sera composé de fichiers de données au moyen des propriétés dct:hasPart/dct:isPartOf conformément à la recommandations définie
  dans https://joinup.ec.europa.eu/release/dcat-ap-how-model-dataset-series.  
  - Un fichier de données DiDo sera représenté en DCAT par un dcat:Dataset qui fera partie d'un jeu de données DiDo ;
    il définira une propriété dct:conforms_to vers un fichier contenant un schéma JSON du fichier de données.
  - Un fichier annexe sera représenté par un foaf:Document référencé depuis le jeu de données au travers d'une propriété foaf:page.
  - Un millesime sera représenté en DCAT par un dcat:Distribution.  
  - Une organisation DiDo sera représentée comme foaf:Organization.  
  - Les thémes DiDo seront structurés en skos:Concept structurés dans un skos:ConceptScheme, un mapping de ces thèmes vers le vocabulaire data-theme sera proposé.  
  - Un mot-clé DiDo sera représenté par un rdfs:Literal lié par la propriété dcat:keyword.

Dans un premier temps, il n'est pas envisagé de représenter les API DiDo comme dcat:DataService car cette dernière classe est spécifique de DCAT v2.
La solution sera d'indiquer dans les dcat:Distribution correspondant à un millesime un lien vers un fichier CSV généré par DiDo.
