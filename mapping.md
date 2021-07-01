## Correspondance de DiDo vers DCAT
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
  - fichiers annexes -> dcat:Distribution
  - fichiers de données -> dcat:Dataset
  - millesime -> dcat:Distribution
  - organisation -> foaf:Organization
  - theme -> skos:Concept
  - mot-clé -> rdfs:Literal

Un jeu de données DiDo sera représenté en DCAT par un dcat:Dataset qui sera composé de fichiers de données au moyen des propriétés dct:hasPart/dct:isPartOf conformément à la recommandations définie dans GeoDCAT-AP.
Un fichier de données DiDo sera représenté en DCAT par un dcat:Dataset qui fera partie d'un jeu de données DiDo.
Un fichier annexe sera représenté en DCAT par une dcat:Distribution, cette représentation n'est pas parfaitement adaptée mais permet de gérer simplement les métadonnées de ce fichier annexe.
Un millesime sera représenté en DCAT par un dcat:Distribution.
Les organisations DiDo seront structurées comme foaf:Organization.
Les thémes DiDo seront structurés en skos:Concept structurés dans un skos:ConceptScheme, un mapping de ces thèmes vers le vocabulaire data-theme sera proposé.
Un mot-clé DiDo sera représenté par un rdfs:Literal lié par la propriété dcat:keyword.

Dans un premier temps, il n'est pas envisagé de représenter les API DiDo comme dcat:DataService car cette dernière classe est spécifique de DCAT v2.
La solution sera d'indiquer dans les dcat:Distribution correspondant à un millesime un lien vers un fichier CSV généré par DiDo.
