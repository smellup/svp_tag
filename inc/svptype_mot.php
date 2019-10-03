<?php
/**
 * Ce fichier contient l'API de gestion des mots-clés propre à SVP Typologie.
 *
 * @package SPIP\SVPTYPE\MOT\API
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Vérifie que le groupe identifié par son id matérialise bien une typologie de plugin.
 *
 * @api
 *
 * @param int $id_groupe Id du groupe de mots concerné.
 *
 * @return bool True si le groupe est celui d'une typologie, false sinon.
 */
function groupe_est_typologie_plugin($id_groupe) {

	// Initialisation du tableau statique des indicateurs de groupe typologique.
	static $est_typologie = array();

	if (!isset($est_typologie[$id_groupe])) {
		$est_typologie[$id_groupe] = false;

		include_spip('inc/config');
		if ($configurations_typologie = lire_config('svptype/typologies', array())) {
			$ids_groupe = array_column($configurations_typologie, 'id_groupe');
			if (in_array($id_groupe, $ids_groupe)) {
				$est_typologie[$id_groupe] = true;
			}
		}
	}

	return $est_typologie[$id_groupe];
}

/**
 * Renvoie l'id du groupe d'un mot-clé.
 *
 * @api
 *
 * @param int $id_mot Id du mot-clé.
 *
 * @return int Id du groupe d'appartenance du mot-clé ou 0 sinon.
 */
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
 * Renvoie le champ `identifiant` d'un mot-clé si celui-ci est un type de plugin.
 * Si le mot-clé n'est pas un type de plugin l'identifiant est vide.
 *
 * @api
 *
 * @param int $id_mot Id du mot-clé.
 *
 * @return string Champ `identifiant` du mot-clé si celui-ci est un type de plugin ou chaine vide sinon.
 */
function mot_lire_identifiant($id_mot) {
	static $identifiants = array();

	if (!isset($identifiants[$id_mot])) {
		$identifiants[$id_mot] = '';

		$from = 'spip_mots';
		$where = array('id_mot=' . intval($id_mot));
		$identifiant = sql_getfetsel('identifiant', $from, $where);
		if ($identifiant !== null) {
			$identifiants[$id_mot] = $identifiant;
		}
	}

	return $identifiants[$id_mot];
}

/**
 * Renvoie les id des enfants d'un mot-clé.
 *
 * @param int $id_mot Id du mot-clé.
 *
 * @return array Tableau des id des enfants d'un mot-clé ou tableau vide sinon.
 */
function mot_lire_enfants($id_mot) {
	static $ids_enfant = array();

	if (!isset($ids_enfant[$id_mot])) {
		$ids_enfant[$id_mot] = array();

		$from = 'spip_mots';
		$where = array('id_parent=' . intval($id_mot));
		$ids = sql_allfetsel('id_mot', $from, $where);
		if ($ids) {
			$ids_enfant[$id_mot] = array_column($ids, 'id_mot');
		}
	}

	return $ids_enfant[$id_mot];
}
