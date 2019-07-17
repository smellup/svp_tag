<?php
/**
 * Gestion du formulaire de vidage d'une typologie de plugin
 * (liste des types ou liste des affectations type-plugin).
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Chargement des données : le formulaire sert à vider les types de plugin ou leurs affacteations. Il est donc
 * nécessaire de construire les libellés des cases à cocher idoines.
 * Le choix des affectations est toujours proposé mais pas celui des types de plugin qui ne peuvent être supprimés
 * que si aucune affectation n'existe.
 *
 * @param string $typologie Typologie de plugin concernée. Prend les valeurs `categorie`, `tag`...
 *
 * @return array Tableau des données à charger par le formulaire :
 *               - `_label_liste`       : (affichage) choix d'exportation entre les types de plugin ou leurs affectations.
 *               - `_label_affectation` : (affichage) indique que des affectations existent pour la typologie.
 *               - `_nb_affectations`   : (affichage) permet d'afficher ou de masquer le choix des types de plugin en JS
 */
function formulaires_vider_typologie_charger($typologie) {

	// Initialisation du tableau des variables fournies au formulaire.
	$valeurs = array();

	// Labels et explication des choix.
	$valeurs['_label_liste'] = _T("svptype:${typologie}_vidage_liste_label");
	$valeurs['_label_affectation'] = _T("svptype:${typologie}_vidage_affectation_label");

	// Autoriser le vidage des types de plugin :
	// - si aucune affectation.
	// - si on demande de vider les affectations

	// Déterminer l'id du groupe pour la typologie concernée.
	include_spip('inc/config');
	$id_groupe = lire_config("svptype/typologies/${typologie}/id_groupe", 0);

	// Compter les affectations
	$valeurs['_nb_affectations'] = sql_countsel(
		'spip_plugins_typologies',
		array(
			'id_groupe=' . $id_groupe
		)
	);

	return $valeurs;
}

/**
 * Exécution du formulaire : vidage des affectations et/ou des types de plugins (dans cet ordre si les deux sont
 * sélectionnés).
 *
 * @param string $typologie Typologie de plugin concernée. Prend les valeurs `categorie`, `tag`...
 *
 * @return array Tableau retourné par le formulaire contenant toujours un message de bonne exécution ou
 *               d'erreur. L'indicateur editable est toujours à vrai.
 */
function formulaires_vider_typologie_traiter($typologie) {

	// Initialisation du retour de traitement du formulaire (message, editable).
	$retour = array();

	// Récupération des saisies
	$vider_affectation = _request('vidage_affectation');
	$vider_liste = _request('vidage_liste');

	// Vérifier qu'un choix a été fait
	if (!$vider_affectation and !$vider_liste) {
		$retour['message_erreur'] = _T('svptype:vidage_message_nok');
	} else {
		// On vide en premier les affectations qui elles peuvent toujours être supprimées sans dommage.
		include_spip('inc/svptype_typologie');
		if ($vider_affectation) {
			typologie_plugin_vider($typologie, 'affectation');
		}

		// On vide les types de plugin qui ne peuvent être supprimés que si il n'existe aucune affectation.
		// -- la vérification a été faite via l'ergonomie du formulaire
		if ($vider_liste) {
			typologie_plugin_vider($typologie, 'liste');
		}

		$retour['message_ok'] = _T('svptype:vidage_message_ok');
	}

	// Retour du formulaire : on reste sur la page de maintenance.
	$retour['editable'] = true;

	return $retour;
}
