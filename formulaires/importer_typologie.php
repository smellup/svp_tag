<?php
/**
 * Gestion du formulaire d'importation d'une typologie de plugin
 * (liste des types ou liste des affectations type-plugin).
 */
if (!defined("_ECRIRE_INC_VERSION")) {
	return;
}

/**
 * Chargement des données : le formulaire sert à tout type d'importation. Il est donc nécessaire de construire le
 * titre du formulaire spécifique à la typologie importée.
 *
 * @param string $typologie
 *        Typologie de plugin concernée. Prend les valeurs `categorie` ou `tag`.
 * @param string $redirect
 *        URL de redirection en fin de traitement : on revient toujours de la page source.
 *
 * @return array
 * 		Tableau des données à charger par le formulaire (affichage).
 * 		- `titre`		 : (affichage) titre du formulaire
 */
function formulaires_importer_typologie_charger($typologie, $redirect = '') {

	// Initialisation du tableau des variables fournies au formulaire.
	$valeurs = array();

	$valeurs['_vues'] = array(
		'liste'       => _T("svptype:${typologie}_import_vue_liste_label"),
		'affectation' => _T("svptype:${typologie}_import_vue_affectation_label"),
	);
	$valeurs['_vue_defaut'] = 'liste';

	return $valeurs;
}

/**
 * Vérification des saisies : il est indispensable de choisir un fichier d'import de type JSON.
 *
 * @param string $typologie
 *        Typologie de plugin concernée. Prend les valeurs `categorie` ou `tag`.
 * @param string $redirect
 *        URL de redirection en fin de traitement : on revient toujours de la page source.
 *
 * @return array
 * 		Tableau des erreurs concernant le fichier ou tableau vide si aucune erreur.
 */
function formulaires_importer_typologie_verifier($typologie, $redirect = '') {

	// Initialisation des messages d'erreur
	$erreurs = array();

	$champ = 'fichier_import';
	if (empty($_FILES[$champ]['name'])) {
		// Aucun fichier choisi.
		$erreurs[$champ] = _T('info_obligatoire');
	} else {
		// Le fichier choisi doit être un JSON
		if (empty($_FILES[$champ]['type'])
		or ($_FILES[$champ]['type'] != 'application/json')) {
			$erreurs[$champ] = _T('svptype:import_message_nok_json');
		}
	}

	return $erreurs;
}

/**
 * Exécution du formulaire : le fichier choisi est décodé et les types contenus sont chargés en base si il ne
 * sont pas déjà présents.
 *
 * @param string $typologie
 *        Typologie de plugin concernée. Prend les valeurs `categorie` ou `tag`.
 * @param string $redirect
 *        URL de redirection en fin de traitement : on revient toujours à la page source.
 *
 * @return array
 * 		Tableau retourné par le formulaire contenant toujours un message de bonne exécution ou
 * 		d'erreur. L'indicateur editable est toujours à vrai.
 */
function formulaires_importer_typologie_traiter($typologie, $redirect = '') {

	// Initialisation du retour de traitement du formulaire (message, editable).
	$retour = array();
	$resultat_import = false;

	// Récupération des saisies
	$vue = _request('vue_export');

	if ($_FILES['fichier_import']['name'] != '') {
		// Récupération du fichier, décodage du contenu JSON et importation en base.
		// -- Création du répertoire d'upload
		$dir = sous_repertoire(_DIR_TMP, 'svptype');

		// -- Détermination du nom du fichier temporaire de façon à ce qu'il soit unique.
		$hash = md5('import-' . $GLOBALS['visiteur_session']['id_auteur'] . time());
		$fichier = $dir . $hash . '-' . $_FILES['fichier_import']['name'];

		// -- Déplacement du fichier téléchargé dans la destination choisie.
		if (move_uploaded_file($_FILES['fichier_import']['tmp_name'], $fichier)) {
			// -- Lecture et suppression du fichier temporaire
			include_spip('inc/flock');
			lire_fichier($fichier, $contenu);
			@unlink($fichier);

			// -- Décodage du contenu JSON en tableau PHP.
			$liste = json_decode($contenu, true);

			// -- Importation du tableau représentant la typologie.
			if ($liste) {
				include_spip('inc/svptype_typologie');
				$suffixe = $vue == 'liste' ? '' : "_${vue}";
				$importer = "typologie_plugin_importer${suffixe}";
				$resultat_import = $importer($typologie, $liste);
			}
		}
	}

	// Retour du formulaire.
	if ($resultat_import) {
		$retour['message_ok'] = _T('svptype:import_message_ok', array('nb' => $resultat_import));
	} else {
		$retour['message_nok'] = _T('svptype:import_message_nok');
	}
	$retour['redirect'] = $redirect;
	$retour['editable'] = true;

	return $retour;
}
