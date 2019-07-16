<?php
/**
 * Gestion du formulaire de vidage d'une typologie de plugin
 * (liste des types ou liste des affectations type-plugin).
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Chargement des données : le formulaire sert à tout type d'importation. Il est donc nécessaire de construire le
 * choix d'exportation entre les types de plugins ou leurs affectations.
 *
 * @param string $typologie
 *                          Typologie de plugin concernée. Prend les valeurs `categorie` ou `tag`.
 *
 * @return array
 *               Tableau des données à charger par le formulaire (affichage) :
 *               - `_vues`         : (affichage) choix d'exportation entre les types de plugin ou leurs affectations.
 *               - `_est_affectee` : indique que des affectations existent pour la typologie.
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
	$valeurs['_nb_affectations'] = sql_countsel('spip_plugins_typologies', array('id_groupe=' . $id_groupe));

	return $valeurs;
}

/**
 * Vérification des saisies : il est indispensable de choisir un fichier d'import de type JSON.
 *
 * @param string $typologie
 *                          Typologie de plugin concernée. Prend les valeurs `categorie` ou `tag`.
 *
 * @return array
 *               Tableau des erreurs concernant le fichier ou tableau vide si aucune erreur.
 */
function formulaires_vider_typologie_verifier($typologie) {

	// Initialisation des messages d'erreur
	$erreurs = array();

	return $erreurs;
}

/**
 * Exécution du formulaire : le fichier choisi est décodé et les types de plugin ou les affectations sont chargés
 * en base si il ne sont pas déjà présents.
 *
 * @param string $typologie
 *                          Typologie de plugin concernée. Prend les valeurs `categorie`, `tag`...
 *
 * @return array
 *               Tableau retourné par le formulaire contenant toujours un message de bonne exécution ou
 *               d'erreur. L'indicateur editable est toujours à vrai.
 */
function formulaires_vider_typologie_traiter($typologie) {

	// Initialisation du retour de traitement du formulaire (message, editable).
	$retour = array();

	// Récupération des saisies
	$vider_affectation = _request('vidage_affectation');
	$vider_liste = _request('vidage_liste');

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

	// Retour du formulaire : on reste sur la page de maintenance.
	$retour['redirect'] = '';
	$retour['editable'] = true;

	return $retour;
}
