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
 * @param string $type_data
 *        Type de données à exporter. Prend les valeurs :
 *        - `liste` pour indiquer qu'on veut exporter la liste des types d'une typologie
 *        - `affectation` pour indiquer qu'on veut exporter des affectations type-plugin.
 * @param string $redirect
 *        URL de redirection en fin de traitement : aucune, on reste sur la page d'export.
 *
 * @return array
 * 		Tableau des données à charger par le formulaire (affichage).
 * 		- `titre`		 : (affichage) titre du formulaire
 */
function formulaires_exporter_typologie_charger($typologie, $type_data, $redirect = '') {

	// Initialisation du tableau des variables fournies au formulaire.
	$valeurs = array();

	$valeurs['_titre'] = _T("svptype:${typologie}_export_${type_data}_form_titre");

	return $valeurs;
}


/**
 * Exécution du formulaire : les types sont exportés dans un fichier JSON dont le format est compatible avec
 * celui de l'importation. Suivant le choix fait, le fichier est soit créé dans le répertoire idoine soit mis à
 * disposition au téléchargement.
 *
 * @param string $typologie
 *        Typologie de plugin concernée. Prend les valeurs `categorie` ou `tag`.
 * @param string $type_data
 *        Type de données à exporter. Prend les valeurs :
 *        - `liste` pour indiquer qu'on veut exporter la liste des types d'une typologie
 *        - `affectation` pour indiquer qu'on veut exporter des affectations type-plugin.
 * @param string $redirect
 *        URL de redirection en fin de traitement : aucune, on reste sur la page d'export.
 *
 * @return array
 * 		Tableau retourné par le formulaire contenant toujours un message de bonne exécution ou
 * 		d'erreur. L'indicateur editable est toujours à vrai.
 */
function formulaires_exporter_typologie_traiter($typologie, $type_data, $redirect = '') {

	// Initialisation du retour de traitement du formulaire (message, editable).
	$retour = array();

	// Copnstruction de la fonction d'export.
	include_spip('inc/svptype_typologie');
	$suffixe = $type_data == 'liste' ? '' : "_${type_data}";
	$exporter = "typologie_plugin_exporter${suffixe}";

	// Création du fichier d'export en local.
	if ($fichier = $exporter($typologie)) {
		// Si la demande est de télécharger le fichier dans la foulée on active cette option.
		if (_request('choix_export') == 'local') {
			// Telechargement du fichier cache (.xml)
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate");
			header("Cache-Control: private", false);
			header('Content-Type: application/json');
			header("Content-Length: ".filesize($fichier));
			header("Content-Disposition: attachment; filename=\"".basename($fichier)."\"");
			header("Content-Transfer-Encoding: binary");
			@readfile($fichier);
			exit();
		}
	} else {
		$retour['message_nok'] = _T('svptype:export_message_nok');
	}

	// Retour du formulaire.
	if (empty($retour['message_nok'])) {
		$retour['message_ok'] = _T('svptype:export_message_ok');
	}
	$retour['redirect'] = $redirect;
	$retour['editable'] = true;

	return $retour;
}
