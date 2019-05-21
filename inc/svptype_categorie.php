<?php
/**
 * Ce fichier contient l'API de gestion des contrôles.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}




function groupe_lire_identifiant($id_groupe) {

	static $identifiants = array();

	if (!isset($identifiants[$id_groupe])) {
		$identifiants[$id_groupe] = '';

		$from = 'spip_rubriques';
		$where = array('id_rubrique=' . intval($id_groupe));
		$categorie = sql_getfetsel('identifiant', $from, $where);
		if ($categorie !== null) {
			$identifiants[$id_groupe] = $categorie;
		}
	}

	return $identifiants[$id_groupe];
}

/**
 * Vérifie que la rubrique concernée fait bien partie d'un secteur-plugin.
 * Il suffit de vérifier que le secteur a bien une catégorie non vide.
 *
 * @param int $id
 * 		Id de la rubrique concernée.
 *
 * @return bool
 *       True si la rubrique fait partie d'un secteur-plugin, false sinon.
 */
function groupe_est_plugin($id_groupe) {

	static $est_plugin = array();

	if (!isset($est_plugin[$id_groupe])) {
		$est_plugin[$id_groupe] = false;

		if (groupe_lire_identifiant($id_groupe)) {
			$est_plugin[$id_groupe] = true;
		}
	}

	return $est_plugin[$id_groupe];
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
		$select = array('m.*');
		$where = array(
			''
		);
		$categories = sql_allfetsel($select, $from, $where);
	}

    return $categories;
}
