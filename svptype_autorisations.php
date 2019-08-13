<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// fonction pour le pipeline, n'a rien a effectuer
function svptype_autoriser() {
}

/**
 * Autorisation de supprimer un type de plugin.
 *
 * Un type de plugin est un mot-cle technique pouvant être arborescent ou pas ce qui implique de vérifier :
 * - l'autorisation de suppression d'un mot (plugin mots)
 * - l'autorisation de suppression d'un mots arborescents, ie pas d'enfant (plugin mots arborescents)
 * - le type n'est pas encore affecté à un plugin
 *
 * @param string $faire Action demandée
 * @param string $type  Type d'objet sur lequel appliquer l'action
 * @param int    $id    Identifiant de l'objet
 * @param array  $qui   Description de l'auteur demandant l'autorisation
 * @param array  $opt   Options de cette autorisation
 *
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_typeplugin_supprimer($faire, $type, $id, $qui, $opt) {

	// Initialisation de l'autorisation
	$autoriser = false;

	// Vérification préalable de l'autorisation de suppression d'un 'mot'
	// qui combine déjà celle du plugin mots et celle du plugin mots arborescents.
	if (autoriser('supprimer', 'mot', $id, $qui, $opt)) {
		include_spip('inc/svptype_mot');
		$id_mot = intval($id);
		$id_groupe = mot_lire_groupe($id_mot);
		if (groupe_est_typologie_plugin($id_groupe)) {
			// Le mot est un type de plugin, il faut vérifier :
			// -- que le type ne doit avoir aucune affectation de plugins car on sait que c'est une feuille
			$where = array(
				'id_groupe=' . $id_groupe,
				'id_mot=' . $id_mot
			);
			if (!sql_countsel('spip_plugins_typologies', $where)) {
				$autoriser = true;
			}
		}
	}

	return $autoriser;
}

/**
 * Autorisation de modifier un type de plugin.
 *
 * Un type de plugin est un mot-cle technique pouvant être arborescent ou pas ce qui implique de vérifier :
 * - l'autorisation de modification d'un mot (plugin mots)
 *
 * @param string $faire Action demandée
 * @param string $type  Type d'objet sur lequel appliquer l'action
 * @param int    $id    Identifiant de l'objet
 * @param array  $qui   Description de l'auteur demandant l'autorisation
 * @param array  $opt   Options de cette autorisation
 *
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_typeplugin_modifier($faire, $type, $id, $qui, $opt) {

	// Initialisation de l'autorisation
	$autoriser = false;

	// Vérification préalable de l'autorisation standard du plugin 'mots'.
	if (autoriser('modifier', 'mot', $id, $qui, $opt)) {
		$autoriser = true;
	}

	return $autoriser;
}

/**
 * Autorisation de créer un type de plugin.
 *
 * Un type de plugin est un mot-cle technique pouvant être arborescent ou pas ce qui implique de vérifier :
 * - l'autorisation de création d'un mot (plugin mots)
 *
 * @param string $faire Action demandée
 * @param string $type  Type d'objet sur lequel appliquer l'action
 * @param int    $id    Identifiant de l'objet
 * @param array  $qui   Description de l'auteur demandant l'autorisation
 * @param array  $opt   Options de cette autorisation. Contient le groupe de mots dans lequel créer le mot.
 *
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_typeplugin_creer($faire, $type, $id, $qui, $opt) {

	// Initialisation de l'autorisation
	$autoriser = false;

	// Vérification préalable de l'autorisation standard du plugin 'mots'.
	if (autoriser('creer', 'mot', $id, $qui, $opt)) {
		$autoriser = true;
	}

	return $autoriser;
}

/**
 * Autorisation, pour un plugin, de lui affecter un type de plugin, de lui supprimer ou de lui modifier
 * une affectation existante.
 *
 * @param string $faire Action demandée
 * @param string $type  Type d'objet sur lequel appliquer l'action
 * @param int    $id    Identifiant de l'objet
 * @param array  $qui   Description de l'auteur demandant l'autorisation
 * @param array  $opt   Options de cette autorisation. Contient le groupe de mots dans lequel créer le mot.
 *
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_plugin_affecter($faire, $type, $id, $qui, $opt) {

	// Initialisation de l'autorisation
	$autoriser = autoriser('webmestre');

	return $autoriser;
}

/**
 * Autorisation de créer un mot.
 *
 * Surcharge l'autorisation du plugin mots pour ne pas afficher le bouton de création dans la page d'un type de
 * plugin.
 *
 * @param string $faire Action demandée
 * @param string $type  Type d'objet sur lequel appliquer l'action
 * @param int    $id    Identifiant de l'objet
 * @param array  $qui   Description de l'auteur demandant l'autorisation
 * @param array  $opt   Options de cette autorisation
 *
 * @return bool true s'il a le droit, false sinon
**/
function autoriser_mot_creer2($faire, $type, $id, $qui, $opt) {

	// Initialisation de l'autorisation
	$autoriser = false;

	// si l'autorisation normale ne passe déjà pas, partir !
	if (autoriser_mot_creer_dist($faire, $type, $id, $qui, $opt)) {
		// On vérifie qu'on est pas sur la page de visualisation d'un type de plugin.
		$exec = _request('exec');
		if ($exec != 'type_plugin') {
			// On vérifie qu'on est pas en présence d'un type de plugin.
			include_spip('inc/svptype_mot');
			$id_mot = intval($id);
			$id_groupe = mot_lire_groupe($id_mot);
			if (groupe_est_typologie_plugin($id_groupe)) {
				$autoriser = true;
			}
		}
	}

	return $autoriser;
}
