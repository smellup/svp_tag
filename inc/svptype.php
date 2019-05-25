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

		$from = 'spip_groupes_mots';
		$where = array('id_groupe=' . intval($id_groupe));
		$categorie = sql_getfetsel('identifiant', $from, $where);
		if ($categorie !== null) {
			$identifiants[$id_groupe] = $categorie;
		}
	}

	return $identifiants[$id_groupe];
}


function groupe_lire_id($identifiant) {

	static $ids_groupe = array();

	if (!isset($ids_groupe[$identifiant])) {
		$ids_groupe[$identifiant] = 0;

		$from = 'spip_groupes_mots';
		$where = array('identifiant=' . sql_quote($identifiant));
		$id = sql_getfetsel('id_groupe', $from, $where);
		if ($id !== null) {
			$ids_groupe[$identifiant] = intval($id);
		}
	}

	return $ids_groupe[$identifiant];
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

		if (in_array(groupe_lire_identifiant($id_groupe), groupe_plugin_lister())) {
			$est_plugin[$id_groupe] = true;
		}
	}

	return $est_plugin[$id_groupe];
}


/**
 * Renvoie la liste des identifiants de groupe plugin.
 *
 * @return array
 *       Identifiants des groupes.
 */
function groupe_plugin_lister() {

	include_spip('inc/config');
	if ($groupes = lire_config('svptype/groupes', array())) {
		$groupes = array_column($groupes, 'identifiant');
	}

	return $groupes;
}


function mot_lire_groupe($id_mot) {

	static $ids_groupe = array();

	if (!isset($ids_groupe[$id_mot])) {
		$ids_groupe[$id_mot] = 0;

		$from = 'spip_mots';
		$where = array('id_mot=' . intval($id_mot));
		$id = sql_getfetsel('id_groupe', $from, $where);
		if ($id !== null) {
			$ids_groupe[$id_mot] = intval($id);
		}
	}

	return $ids_groupe[$id_mot];
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
		// On récupère l'id du groupe plugin-categorie
		$id_groupe = groupe_lire_id('plugin-categorie');

		// On récupère la description complète de toutes les catégories de plugin
		$from = array('spip_mots');
		$where = array('id_groupe=' . $id_groupe);
		$order_by = array('identifiant');
		$categories = sql_allfetsel('*', $from, $where, '', $order_by);
	}

	// Application des filtres éventuellement demandés en argument de la fonction
	$categories_filtrees = $categories;
	if ($filtres) {
		foreach ($categories_filtrees as $_categorie) {
			foreach ($filtres as $_critere => $_valeur) {
				if (isset($_description[$_critere]) and ($_categorie[$_critere] != $_valeur)) {
					unset($categories_filtrees[$_categorie]);
					break;
				}
			}
		}
	}

	if ($information) {
		$categories_filtrees = array_column($categories_filtrees, $information);
	}

    return $categories_filtrees;
}
