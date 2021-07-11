<?php
/*PhpDoc:
name: pagination.inc.php
title: calcule les paramètres de pagination du catalogue
doc: |
  Principes de la pagination:
    - le num de page commence à 1, c'est le numéro par défaut
    - la taille des pages par défaut est 10 ; si elle vaut 'all' alors pas de pagination
    - les définitions des vocabulaires sont dans la page 1
    - les datasets (JD/DFiles/Ref/Nom) sont répartis dans les pages
journal: |
  11/7/2021:
    - création
*/

class Pagination {
  public int $page; // no de page
  public int|string $page_size; // taille de la page
  public string $sql; // requête SQL à utiliser pour sélectionner les ressources DCAT
  public array $refNomDsUrisSel; // sélection des URIs de réf. et nom.
  public int $totalItems; // nbre total de ressources contenues dans le catalogue
  
  function __construct() {
    $page = $_GET['page'] ?? 1;
    $page_size = $_GET['page_size'] ?? 10;

    // calcul des numéros de ds à récupérer dans PgSql, définition de la requête SQL dans la variable $sql
    $refNomDsUris = RefNom::dsUris(); // Les URIS des datasets réf. et nomenclature
    // calcul du bre de dcat:Dataset dans la base
    $sql = "select count(*) nbre from didodcat
            where uri like 'https://dido.geoapi.fr/id/datasets/%' 
               or (uri like 'https://dido.geoapi.fr/id/datafiles/%' and dido is null)";
    $nbrDsData = PgSql::getTuples($sql)[0]['nbre'] + 0;
    //echo "nbrDsData=$nbrDsData\n";
    $totalItems = count($refNomDsUris) + $nbrDsData;
    if ($page_size == 'all') { // pas de pagination
      $page = 1;
      $sql = "select dcat from didodcat"; // Tout à sélectionner
      $refNomDsUrisSel = $refNomDsUris;
    }
    else {
      $refNomDsUrisSel = [];
      if ($page * $page_size <= count($refNomDsUris)) { // alors la page est composée uniquement de refnoms
        // recopie d'une portion des refNomDsUris
        //echo "sélection des RefNoms ",($page-1)*$page_size," inclus à ",$page*$page_size," exclus<br>\n";
        for($no = ($page-1)*$page_size; $no < $page*$page_size; $no++) {
          $refNomDsUrisSel[] = $refNomDsUris[$no];
        }
        $sql = ''; // aucun DS de la base
      }
      elseif (($page-1) * $page_size < count($refNomDsUris)) {
        //echo "sélection des RefNoms ",($page-1)*$page_size," inclus à ",count($refNomDsUris)," exclus<br>\n";
        for($no = ($page-1)*$page_size; $no < count($refNomDsUris); $no++) {
          $refNomDsUrisSel[] = $refNomDsUris[$no];
        }
        $dsmin = 0;
        $dsmax = $page*$page_size - count($refNomDsUris);
        $sql = "select dcat from didodcat where dsnum >= $dsmin and dsnum < $dsmax";
      }
      else {
        $dsmin = ($page-1)*$page_size - count($refNomDsUris);
        $dsmax = $page*$page_size - count($refNomDsUris);
        $sql = "select dcat from didodcat where dsnum >= $dsmin and dsnum < $dsmax";
      }
    }
    //echo "<pre>"; die("sql='$sql'\n");
    //echo "<pre>"; print_r($_SERVER); die();
    $this->page = $page;
    $this->page_size = $page_size;
    $this->sql = $sql;
    $this->refNomDsUrisSel = $refNomDsUrisSel;
    $this->totalItems = $totalItems;
  }
};
