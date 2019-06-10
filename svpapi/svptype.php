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
	$id_groupe = lire_config("svptype/typologies/${typologie}/id_groupe", 0);
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

	// Vérifier si un filtre de typologie est fourni et si oui récupérer les informations associées.
	$typologie = '';
	if (isset($filtres['typologie'])) {
		// Initialisation de la typologie
		$typologie = $filtres['typologie'];

		// Récupérer les informations sur le groupe de mots matérialisant cette typologie.
		include_spip('inc/config');
		$id_groupe = lire_config("svptype/typologies/${typologie}/id_groupe", 0);
		$select = array('titre', 'identifiant');
		$where = array('id_groupe=' . intval($id_groupe));
		$affectations['groupe'] = sql_fetsel($select, 'spip_groupes_mots', $where);

		// On supprime la typologie des filtres car le traitement est particulier
		unset($filtres['typologie']);
	}

	// Récupération du couple (identifiant du type, préfixe du plugin) de chaque affectation.
	// -- Initialisation de la jointure avec spip_mots
	$from = array('spip_plugins_typologies', 'spip_mots');
	$select = array('spip_plugins_typologies.prefixe as prefixe', 'spip_mots.identifiant as type');

	// -- Initialisation du where avec la conditions sur la jointure.
	$where = array('spip_plugins_typologies.id_mot=spip_mots.id_mot');
	// -- Traitement de la typologie si elle est définie.
	if ($typologie) {
		$where[] = 'spip_plugins_typologies.id_groupe=' . intval($id_groupe);
	}
	$affectations['affectations'] = sql_allfetsel($select, $from, $where);
	$affectations['affectations'] = array_column($affectations['affectations'], 'type', 'prefixe');

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

	// Acquisition de la liste des catégories affectables.
	include_spip('inc/svptype_typologie');
	$filtres = array('profondeur' => 1);
	$informations = array('identifiant');
	$categories = type_plugin_repertorier('categorie', $filtres, $informations);

	// Test de validité
	if (!in_array($valeur, array_column($categories, 'identifiant'))) {
		$est_valide = false;
		$extra = 'voir la liste à URL xxx';
	}

	return $est_valide;
}
