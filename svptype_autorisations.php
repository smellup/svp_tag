<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// fonction pour le pipeline, n'a rien a effectuer
function svptype_autoriser() {
}

/**
 * Autorisation minimale d'accès à toutes les pages ds SVP Typologie.
 * Par défaut, seuls les administrateurs complets sont autorisés à utiliser le plugin.
 * Cette autorisation est à la base de la plupart des autres autorisations du plugin.
 *
 * @param $faire
 * @param $type
 * @param $id
 * @param $qui
 * @param $options
 *
 * @return bool
 */
function autoriser_typologie_dist($faire, $type, $id, $qui, $options) {
	return autoriser('defaut');
}

/**
 * Autorisation de supprimer un type de plugin.
 *
 * Un type de plugin est un mot-cle technique pouvant être arborescent ou pas ce qui implique de vérifier :
 * - l'autorisation minimale de typologie
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
function autoriser_typeplugin_supprimer_dist($faire, $type, $id, $qui, $opt) {

	// Initialisation de l'autorisation
	$autoriser = false;

	// Vérification préalable de l'autorisation de suppression d'un 'mot'
	// qui combine déjà celle du plugin mots et celle du plugin mots arborescents.
	if (autoriser('supprimer', 'mot', $id, $qui, $opt)
	and autoriser('typologie')) {
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
 * - l'autorisation minimale de typologie
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
function autoriser_typeplugin_modifier_dist($faire, $type, $id, $qui, $opt) {

	// Initialisation de l'autorisation
	$autoriser = false;

	// Vérification préalable de l'autorisation standard du plugin 'mots'.
	if (autoriser('modifier', 'mot', $id, $qui, $opt)
	and autoriser('typologie')) {
		$autoriser = true;
	}

	return $autoriser;
}

/**
 * Autorisation de créer un type de plugin.
 *
 * Un type de plugin est un mot-cle technique pouvant être arborescent ou pas ce qui implique de vérifier :
 * - l'autorisation minimale de typologie
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
function autoriser_typeplugin_creer_dist($faire, $type, $id, $qui, $opt) {

	// Initialisation de l'autorisation
	$autoriser = false;

	// Vérification préalable de l'autorisation standard du plugin 'mots'.
	if (autoriser('creer', 'mot', $id, $qui, $opt)
	and autoriser('typologie')) {
		$autoriser = true;
	}

	return $autoriser;
}

/**
 * Autorisation, pour un plugin, de lui affecter un type de plugin, de lui supprimer ou de lui modifier
 * une affectation existante.
 *
 * L'autorisation est générique et ne dépend pas du plugin concerné :
 * - l'autorisation minimale de typologie soit les administrateurs complets.
 *
 * @param string $faire Action demandée
 * @param string $type  Type d'objet sur lequel appliquer l'action
 * @param int    $id    Identifiant de l'objet
 * @param array  $qui   Description de l'auteur demandant l'autorisation
 * @param array  $opt   Options de cette autorisation. Contient le groupe de mots dans lequel créer le mot.
 *
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_plugin_affecter_dist($faire, $type, $id, $qui, $opt) {

	// Initialisation de l'autorisation
	$autoriser = autoriser('typologie');

	return $autoriser;
}

/**
 * Autorisation d'affichage du menu d'accès à gestion des typologies de plugin (page=svptype_typologie).
 * Il faut être autorisé à utiliser le plugin.
 *
 * @param $faire
 * @param $type
 * @param $id
 * @param $qui
 * @param $options
 *
 * @return bool
 */
function autoriser_typologie_menu_dist($faire, $type, $id, $qui, $options) {

	// Initialisation de l'autorisation
	$autoriser = autoriser('typologie');

	return $autoriser;
}
