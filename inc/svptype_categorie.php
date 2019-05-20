<?php
/**
 * Ce fichier contient l'API de gestion des contrôles.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Renvoie l'information brute demandée pour l'ensemble des contrôles utilisés
 * ou toute les descriptions si aucune information n'est explicitement demandée.
 *
 * @param array  $filtres
 *        Identifiant d'un champ de la description d'un contrôle.
 * @param string $information
 *        Identifiant d'un champ de la description d'un contrôle.
 *        Si l'argument est vide, la fonction renvoie les descriptions complètes et si l'argument est
 *        un champ invalide la fonction renvoie un tableau vide.
 *
 * @return array
 *        Tableau de la forme `[type_controle]  information ou description complète`. Les champs textuels
 *        sont retournés en l'état, le timestamp `maj n'est pas fourni.
 */
function categorie_plugin_repertorier($filtres = array(), $information = '') {

	// Utilisation d'une statique pour éviter les requêtes multiples sur le même hit.
	static $categories = array();

	if (!$categories) {
		// On récupère la description complète de toutes les catégories de plugin
		$from = array('spip_mots AS m', 'spip_groupes_mots AS gm');
		$select('m.*');
		$where = array(
			''
		);
		$categories = sql_allfetsel($select, $from, $where);
	}

	if ($niveau == 1) {
	// On ne veut que les catégorie de niveau 1
	$categories = array_keys($svp_categories);
	} elseif ($niveau == 2) {
	// On veut soit toutes les catégories de niveau 2, soit les catégories de niveau 2 d'une catégorie niveau 1
	if ($categorie and array_key_exists($categorie, $svp_categories)) {
	$categories = $svp_categories[$categorie];
	} else {
	foreach ($svp_categories as $_categorie => $_sous_categories) {
	foreach ($_sous_categories as $_sous_categorie) {
	$categories[] = $_sous_categorie;
	}
	}
	}
	} else {
	// On veut toutes les catégories et sous-catégories à plat
	foreach ($svp_categories as $_categorie => $_sous_categories) {
	$categories[] = $_categorie;
	foreach ($_sous_categories as $_sous_categorie) {
	$categories[] = $_sous_categorie;
	}
	}
	}


    return $categories;
}
