<?php
/**
 * Ce fichier contient l'API de gestion des mots-clés propre à SVP Typologie.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Vérifie que le groupe identifié par son id matérialise bien une typologie de plugin.
 *
 * @param int $id_groupe
 * 		Id du groupe de mots concerné.
 *
 * @return bool
 *       True si le groupe est celui d'une typologie, false sinon.
 */
function groupe_est_typologie_plugin($id_groupe) {

	static $est_typologie = array();

	if (!isset($est_typologie[$id_groupe])) {
		$est_typologie[$id_groupe] = false;

		include_spip('inc/config');
		if ($typologies = lire_config('svptype/typologies', array())) {
			$ids_groupe = array_column($typologies, 'id_groupe');
			if (in_array($id_groupe, $ids_groupe)) {
				$est_typologie[$id_groupe] = true;
			}
		}
	}

	return $est_typologie[$id_groupe];
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
