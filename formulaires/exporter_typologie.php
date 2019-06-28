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

	// Extraction des données requises pour la typologie concernée.
	include_spip('inc/svptype_type_plugin');
	$objet = $type_data == 'liste' ? 'type_plugin' : "type_plugin_${type_data}";
	$exporter = "${objet}_exporter";
	$types = $exporter($typologie);

	if ($types) {
		// Construction du nom du fichier
		$date = date('Y-m-d_H-i');
		$nom_fichier = "${typologie}_${type_data}_${date}.json";

		// Formatage du contenu exportés en json;
		$export = json_encode($types, JSON_PRETTY_PRINT);

		if (_request('choix_export') == 'local') {
			header('Content-Type: application/json;');
			header("Content-Disposition: attachment; filename=${nom_fichier}");
			header('Content-Length: ' . strlen($export));
			echo $export;
			exit;
		} else {
			// -- Création du répertoire d'upload
			$dir = sous_repertoire(_DIR_TMP, 'svptype');
			$fichier = $dir . $nom_fichier;
			if (!ecrire_fichier($fichier, $export)) {
				$retour['message_nok'] = _T('svptype:export_message_nok');
			}
		}
	} else {
		$retour['message_nok'] = _T('svptype:export_message_vide');
	}

	// Retour du formulaire.
	if (empty($retour['message_nok'])) {
		$retour['message_ok'] = _T('svptype:export_message_ok');
	}
	$retour['redirect'] = $redirect;
	$retour['editable'] = true;

	return $retour;
}
