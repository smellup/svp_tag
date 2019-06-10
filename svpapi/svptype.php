<?php
/**
 * Ce fichier contient l'ensemble des fonctions de service spécifiques à une collection ou une ressource.
 *
 * @package SPIP\SVPAPI\SERVICE
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Récupère la liste des catégories de la table spip_mots éventuellement filtrée par profondeur.
 *
 * @param array $filtres
 *      Tableau des critères de filtrage additionnels.
 *
 * @return array
 *      Tableau des catégories.
 */
function categories_collectionner($filtres) {

	// Initialisation de la collection
	$categories = array();

	// Initialisation de la typologie
	$typologie = 'categorie';

	// Récupérer les informations sur le groupe de mots.
	include_spip('inc/config');
	$id_groupe = lire_config('svptype/typologies/categorie/id_groupe', 0);
	$select = array('titre', 'identifiant');
	$where = array('id_groupe=' . intval($id_groupe));
	$categories['groupe'] = sql_fetsel($select, 'spip_groupes_mots', $where);

	// Récupérer la liste des catégories (filtrée ou pas).
	// -- Extraction des seuls champs significatifs.
	$informations = array(
		'titre',
		'descriptif',
		'id_parent',
		'profondeur',
		'identifiant',
	);
	include_spip('inc/svptype_typologie');
	$collection = type_plugin_repertorier($typologie, $filtres, $informations);

	// On refactore le tableau de sortie en un tableau associatif indexé par les identifiants de catégorie.
	include_spip('inc/svptype_mot');
	if ($collection) {
		$categories['categories'] = array();
		foreach ($collection as $_categorie) {
			$categorie = $_categorie;
			// Identification du parent et suppression de l'id_parent qui devient inutile.
			$categorie['parent'] = $_categorie['id_parent']
				? mot_lire_identifiant($_categorie['id_parent'])
				: '';
			unset($categorie['id_parent']);

			// Déterminer la liste des plugins affectés pour les catégories feuille.
			if ($_categorie['profondeur'] == 1) {
				$affectations = type_plugin_lister_affectation($typologie, $_categorie['identifiant']);
				$categorie['plugins'] = array_column($affectations, 'prefixe');
			}

			// Ajout au tableau de sortie avec l'identifiant en index
			$categories['categories'][$_categorie['identifiant']] = $categorie;
		}
	}

	return $categories;
}


function tags_collectionner($filtres) {
}


/**
 * Récupère la liste des affectations pour une typologie donnée.
 *
 * @param array $filtres
 *      Tableau des critères : permet en particulier de choisir les affectations pour une typologie donnée.
 *
 * @return array
 *      Tableau des affectations.
 */
function affectations_collectionner($filtres) {

	// Initialisation de la collection
	$affectations = array();

	// Vérifier si un filtre de typologie est fourni sinon on le fixe par défaut à catégorie.
	// Récupérer la liste des dépôts (filtrée ou pas).
	$from = array('spip_plugins_affectations');
	// -- Tous le champs sauf maj et id_depot.
	$description_table = lister_tables_objets_sql('spip_depots');
	$select = array_keys($description_table['field']);
	$select = array_diff($select, array('id_depot', 'maj'));

	// -- Initialisation du where avec les conditions sur la table des dépots.
	$where = array();
	// -- Si il y a des critères additionnels on complète le where en conséquence.
	if ($filtres) {
		foreach ($filtres as $_critere => $_valeur) {
			$where[] = "${_critere}=" . sql_quote($_valeur);
		}
	}

	$depots = sql_allfetsel($select, $from, $where);

	return $affectations;
}


/**
 * Détermine si la valeur de la catégorie est valide.
 * La fonction récupère dans le plugin SVP la liste des catégories autorisées.
 *
 * @param string $valeur
 *        La valeur du critère catégorie
 *
 * @return bool
 *        `true` si la valeur est valide, `false` sinon.
 */
function plugins_verifier_critere_categorie($valeur, &$extra) {

	$est_valide = true;

	include_spip('inc/svp_phraser');
	if (!in_array($valeur, $GLOBALS['categories_plugin'])) {
		$est_valide = false;
		$extra = implode(', ', $GLOBALS['categories_plugin']);
	}

	return $est_valide;
}
