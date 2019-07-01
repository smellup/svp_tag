<?php
/**
 * Gestion du formulaire d'exportation d'une typologie de plugin
 * (liste des types ou liste des affectations type-plugin).
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Chargement des données : le formulaire sert à tout type d'exportation. Il est donc nécessaire de construire le
 * titre du formulaire spécifique à la typologie exportée.
 *
 * @param string $typologie
 *        Typologie de plugin concernée. Prend les valeurs `categorie` ou `tag`.
 * @param string $redirect
 *        URL de redirection en fin de traitement : aucune, on reste sur la page d'export.
 *
 * @return array
 * 		Tableau des données à charger par le formulaire (affichage).
 * 		- `titre`		 : (affichage) titre du formulaire
 */
function formulaires_exporter_typologie_charger($typologie, $redirect = '') {

	// Initialisation du tableau des variables fournies au formulaire.
	$valeurs = array();

	$valeurs['_vues'] = array(
		'liste'       => _T("svptype:${typologie}_export_vue_liste_label"),
		'affectation' => _T("svptype:${typologie}_export_vue_affectation_label"),
	);
	$valeurs['_vue_defaut'] = 'liste';

	return $valeurs;
}


/**
 * Exécution du formulaire : les types sont exportés dans un fichier JSON dont le format est compatible avec
 * celui de l'importation. Suivant le choix fait, le fichier est soit créé dans le répertoire idoine soit mis à
 * disposition au téléchargement.
 *
 * @param string $typologie
 *        Typologie de plugin concernée. Prend les valeurs `categorie` ou `tag`.
 * @param string $redirect
 *        URL de redirection en fin de traitement : aucune, on reste sur la page d'export.
 *
 * @return array
 * 		Tableau retourné par le formulaire contenant toujours un message de bonne exécution ou
 * 		d'erreur. L'indicateur editable est toujours à vrai.
 */
function formulaires_exporter_typologie_traiter($typologie, $redirect = '') {

	// Initialisation du retour de traitement du formulaire (message, editable).
	$retour = array();

	// Récupération des saisies
	$vue = _request('vue_export');

	// Copnstruction de la fonction d'export.
	include_spip('inc/svptype_typologie');
	$suffixe = $vue == 'liste' ? '' : "_${vue}";
	$exporter = "typologie_plugin_exporter${suffixe}";

	// Création du fichier d'export en local.
	if ($fichier = $exporter($typologie)) {
		$retour['message_ok'] = _T('svptype:export_message_ok');
	} else {
		$retour['message_nok'] = _T('svptype:export_message_nok');
	}

	// Retour du formulaire.
	$retour['redirect'] = $redirect;
	$retour['editable'] = true;

	return $retour;
}
