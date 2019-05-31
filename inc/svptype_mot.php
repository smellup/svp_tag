<?php
/**
 * Ce fichier contient l'API de gestion de mots propre à SVP Typologie.
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

		include_spip('inc/config');
		if ($groupes = lire_config('svptype/groupes', array())) {
			$groupes = array_column($groupes, 'identifiant');
			if (in_array(groupe_lire_identifiant($id_groupe), $groupes)) {
				$est_plugin[$id_groupe] = true;
			}
		}
	}

	return $est_plugin[$id_groupe];
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


function mot_lire_profondeur($id_mot) {

	static $profondeurs = array();

	if (!isset($profondeurs[$id_mot])) {
		$profondeurs[$id_mot] = 0;

		$from = 'spip_mots';
		$where = array('id_mot=' . intval($id_mot));
		$profondeur = sql_getfetsel('profondeur', $from, $where);
		if ($profondeur !== null) {
			$profondeurs[$id_mot] = intval($profondeur);
		}
	}

	return $profondeurs[$id_mot];
}


function mot_lire_id($identifiant) {

	static $ids_mot = array();

	if (!isset($ids_mot[$identifiant])) {
		$ids_mot[$identifiant] = 0;

		$from = 'spip_mots';
		$where = array('identifiant=' . sql_quote($identifiant));
		$id = sql_getfetsel('id_mot', $from, $where);
		if ($id !== null) {
			$ids_mot[$identifiant] = intval($id);
		}
	}

	return $ids_mot[$identifiant];
}
