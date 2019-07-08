<?php
/**
 * Gestion du formulaire d'exportation d'une typologie de plugin
 * (liste des types de plugin ou liste des affectations type-plugin).
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Chargement des données : le formulaire sert à tout type d'exportation. Il est donc nécessaire de construire le
 * choix d'exportation entre les types de plugins ou leurs affectations.
 *
 * @param string $typologie
 *        Typologie de plugin concernée. Prend les valeurs `categorie` ou `tag`...
 *
 * @return array
 * 		Tableau des données à charger par le formulaire (affichage) :
 * 		- `_vues`       : (affichage) choix d'exportation entre les types de plugin ou leurs affectations.
 *      - `_vue_defaut` : choix par défaut (types de plugin).
 */
function formulaires_exporter_typologie_charger($typologie) {

	// Initialisation du tableau des variables fournies au formulaire.
	$valeurs = array();

	$valeurs['_vues'] = array(
		'liste'       => _T("svptype:${typologie}_export_vue_liste_label"),
		'affectation' => _T("svptype:${typologie}_export_vue_affectation_label"),
	);

	return $valeurs;
}
/**
 * Vérification des saisies : il est indispensable de choisir d'exporter les types de plugin ou leurs affectations.
 *
 * @param string $typologie
 *        Typologie de plugin concernée. Prend les valeurs `categorie` ou `tag`.
 *
 * @return array
 * 		Tableau des erreurs concernant le choix de la vue qui est obligatoire.
 */
function formulaires_exporter_typologie_verifier($typologie) {

	// Initialisation des messages d'erreur
	$erreurs = array();

	$champ = 'vue_export';
	if (empty(_request($champ))) {
		// Aucune vue choisie.
		$erreurs[$champ] = _T('info_obligatoire');
	}

	return $erreurs;
}


/**
 * Exécution du formulaire : les types sont exportés dans un fichier JSON dont le format est compatible avec
 * celui de l'importation. Le fichier est créé dans un sous-répertoire de `_DIR_TMP`.
 *
 * @param string $typologie
 *        Typologie de plugin concernée. Prend les valeurs `categorie` ou `tag`...
 *
 * @return array
 * 		Tableau retourné par le formulaire contenant toujours un message de bonne exécution ou
 * 		d'erreur. L'indicateur editable est toujours à vrai.
 */
function formulaires_exporter_typologie_traiter($typologie) {

	// Initialisation du retour de traitement du formulaire (message, editable).
	$retour = array();

	// Récupération des saisies
	$vue = _request('vue_export');

	// Construction de la fonction d'export.
	include_spip('inc/svptype_typologie');
	$suffixe = ($vue == 'liste' ? '' : "_${vue}");
	$exporter = "typologie_plugin_exporter${suffixe}";

	// Création du fichier d'export sur le serveur.
	if ($fichier = $exporter($typologie)) {
		$retour['message_ok'] = _T('svptype:export_message_ok');
	} else {
		$retour['message_erreur'] = _T('svptype:export_message_nok');
	}

	// Retour du formulaire : on reste sur la page d'export pour visualiser la liste des fichiers d'export.
	$retour['redirect'] = '';
	$retour['editable'] = true;

	return $retour;
}
