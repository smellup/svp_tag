<?php
/**
 * Ce fichier contient l'ensemble des fonctions de service spécifiques à une collection.
 *
 * @package SPIP\SVPTYPE\SVPAPI\SERVICE
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


// -----------------------------------------------------------------------
// ----------------------- COLLECTION CATEGORIES -------------------------
// -----------------------------------------------------------------------

/**
 * Récupère la liste des catégories de la table spip_mots éventuellement filtrée par profondeur.
 *
 * @param array $filtres
 *      Tableau des critères de filtrage additionnels.
 * @param array $configuration
 *      Configuration de la collection catégories utile pour savoir quelle fonction appeler pour construire
 *      chaque filtre.
 *
 * @return array
 *      Tableau des catégories.
 */
function categories_collectionner($filtres, $configuration) {

	// Initialisation de la typologie
	$typologie = 'categorie';

	// Récupérer la collection demandée.
	include_spip('inc/svptype_typologie');
	$categories = typologie_plugin_collectionner($typologie, $filtres);

	return $categories;
}

/**
 * Détermine si la valeur de la profondeur est valide.
 *
 * @param string $profondeur
 *        La valeur du critère profondeur
 *
 * @return bool
 *        `true` si la valeur est valide, `false` sinon.
 */
function categories_verifier_critere_profondeur($profondeur, &$extra) {

	$est_valide = true;

	// Acquisition de la liste des catégories affectables.
	include_spip('inc/config');
	$max_profondeur = lire_config('svptype/typologies/categorie/max_profondeur', 0);

	// Test de validité
	if (intval($profondeur) > $max_profondeur) {
		$est_valide = false;
		$extra = _T('svpapi:extra_max_profondeur', array('max' => $max_profondeur));
	}

	return $est_valide;
}


// -----------------------------------------------------------------------
// -------------------------- COLLECTION TAGS ----------------------------
// -----------------------------------------------------------------------

/**
 * Récupère la liste des tags de la table spip_mots.
 *
 * @param array $filtres
 *      Tableau des critères de filtrage additionnels: toujours vide pour les tags.
 * @param array $configuration
 *      Configuration de la collection catégories utile pour savoir quelle fonction appeler pour construire
 *      chaque filtre.
 *
 * @return array
 *      Tableau des catégories.
 */
function tags_collectionner($filtres, $configuration) {

	// Initialisation de la typologie
	$typologie = 'tag';

	// Récupérer la collection demandée.
	include_spip('inc/svptype_typologie');
	$tags = typologie_plugin_collectionner($typologie, $filtres);

	return $tags;
}


// -----------------------------------------------------------------------
// ---------------------- COLLECTION AFFECTATIONS ------------------------
// -----------------------------------------------------------------------

/**
 * Récupère la liste des affectations pour une typologie donnée.
 *
 * @param array $filtres
 *      Tableau des critères : permet en particulier de choisir les affectations pour une typologie donnée.
 * @param array $configuration
 *      Configuration de la collection affectations utile pour savoir quelle fonction appeler pour construire
 *      chaque filtre (pas utilisée aujourd'hui).
 *
 * @return array
 *      Tableau des affectations indexé par préfixe de plugin.
 */
function affectations_collectionner($filtres, $configuration) {

	// Initialisation de la collection
	$affectations = array();

	// On traite préalablement le filtre typologie :
	// -- celui-ci est forcément présent (obligatoire) et sa valeur est forcément valide.
	$typologie = $filtres['typologie'];

	// Récupérer les informations sur la typologie et le groupe de mots correspondant.
	// -- on loge l'identifiant de la typologie.
	$affectations['typologie'] = array('identifiant' => $typologie);
	// -- on ajoute le titre du groupe de mots
	include_spip('inc/config');
	$configuration_typologie = lire_config("svptype/typologies/${typologie}", array());
	$id_groupe = intval($configuration_typologie['id_groupe']);
	$select = array('titre');
	$where = array('id_groupe=' . $id_groupe);
	$affectations['typologie'] = array_merge(
		$affectations['typologie'],
		sql_fetsel($select, 'spip_groupes_mots', $where)
	);

	// On supprime la typologie des filtres pour ne garder que les filtres optionnels à traiter.
	unset($filtres['typologie']);

	// Récupération du couple (identifiant du type, préfixe du plugin) de chaque affectation.
	// Le tableau de sortie est présenté par préfixe de plugin :
	// - si la typologie n'autorise qu'un type par plugin le tableau est de la forme [prefixe] = [type]
	// - sinon si plusieurs types sont possibles, le tableauu est de la forme [prefixe] = array(types)
	include_spip('inc/svptype_type_plugin');
	$collection = type_plugin_repertorier_affectation($typologie, $filtres);

	// On refactore le tableau de façon à le présenter avec le préfixe en index.
	// -- la liste des types est toujours un tableau même si pour une typologie un seul type est affectable.
	$affectations['affectations'] = array();
	if ($collection) {
		foreach ($collection as $_affectation) {
			$affectations['affectations'][$_affectation['prefixe']][] = $_affectation['identifiant_mot'];
		}
	}

	return $affectations;
}


/**
 * Détermine si la valeur de la profondeur est valide.
 *
 * @param string $typologie
 *        Identifiant de la typologie concernée : categorie, tag...
 *
 * @return bool
 *        `true` si la valeur est valide, `false` sinon.
 */
function affectations_verifier_critere_typologie($typologie, &$extra) {

	$est_valide = true;

	// Acquisition de la liste des catégories affectables.
	include_spip('inc/config');
	$typologies = array_keys(lire_config('svptype/typologies', array()));

	// Test de validité
	if (!in_array($typologie, $typologies)) {
		$est_valide = false;
		$extra = _T(
			'svpapi:extra_typologie',
			array('liste' => implode(', ', $typologies))
		);
	}

	return $est_valide;
}


// -----------------------------------------------------------------------
// ------------------------ COLLECTION PLUGINS ---------------------------
// -----------------------------------------------------------------------

/**
 * Détermine si la valeur de la catégorie est valide.
 * La fonction récupère via l'API du plugin la liste des catégories autorisées.
 *
 * @param string $categorie
 *        La valeur du critère catégorie
 *
 * @return bool
 *        `true` si la valeur est valide, `false` sinon.
 */
function plugins_verifier_critere_categorie($categorie, &$extra) {

	$est_valide = true;

	// Acquisition de la liste des catégories affectables.
	include_spip('inc/svptype_type_plugin');
	$filtres = array('profondeur' => 1);
	$informations = array('identifiant');
	$categories = type_plugin_repertorier('categorie', $filtres, $informations);

	// Test de validité
	if (!in_array($categorie, array_column($categories, 'identifiant'))) {
		$est_valide = false;
		$extra = _T('svpapi:extra_url_liste_categories');
	}

	return $est_valide;
}


/**
 * Construit le critère applicable sur la table spip_plugins pour filtrer la collection sur le
 * critère categorie.
 *
 * @param string $categorie
 *        La valeur du critère catégorie
 *
 * @return string
 *        Chaine représentant le critère sur la catégorie appliqué à la table spip_plugins.
 */
function plugins_construire_critere_categorie($categorie) {

	// On initialise le critère avec une condition toujours fausse.
	$condition = '0=1';

	// On récupère les affectations de plugins pour la catégorie demandée
	include_spip('inc/svptype_type_plugin');
	$filtres = array('type' => $categorie);
	$affectations = type_plugin_repertorier_affectation('categorie', $filtres);

	// Construction de la condition sur les préfixes
	if ($affectations) {
		$plugins = array_column($affectations, 'prefixe');
		$condition = sql_in('prefixe', $plugins);
	}

	return $condition;
}
