<?php
/**
 * Ce fichier contient l'API de gestion des contrôles.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


function groupe_lire_identifiant($id_groupe) {

	static $identifiants = array();

	if (!isset($identifiants[$id_groupe])) {
		$identifiants[$id_groupe] = '';

		$from = 'spip_groupes_mots';
		$where = array('id_groupe=' . intval($id_groupe));
		$categorie = sql_getfetsel('identifiant', $from, $where);
		if ($categorie !== null) {
			$identifiants[$id_groupe] = $categorie;
		}
	}

	return $identifiants[$id_groupe];
}


function groupe_lire_id($identifiant) {

	static $ids_groupe = array();

	if (!isset($ids_groupe[$identifiant])) {
		$ids_groupe[$identifiant] = 0;

		$from = 'spip_groupes_mots';
		$where = array('identifiant=' . sql_quote($identifiant));
		$id = sql_getfetsel('id_groupe', $from, $where);
		if ($id !== null) {
			$ids_groupe[$identifiant] = intval($id);
		}
	}

	return $ids_groupe[$identifiant];
}


/**
 * Vérifie que la rubrique concernée fait bien partie d'un secteur-plugin.
 * Il suffit de vérifier que le secteur a bien une catégorie non vide.
 *
 * @param int $id
 * 		Id de la rubrique concernée.
 *
 * @return bool
 *       True si la rubrique fait partie d'un secteur-plugin, false sinon.
 */
function groupe_est_plugin($id_groupe) {

	static $est_plugin = array();

	if (!isset($est_plugin[$id_groupe])) {
		$est_plugin[$id_groupe] = false;

		if (in_array(groupe_lire_identifiant($id_groupe), groupe_plugin_lister())) {
			$est_plugin[$id_groupe] = true;
		}
	}

	return $est_plugin[$id_groupe];
}


/**
 * Renvoie la liste des identifiants de groupe plugin.
 *
 * @return array
 *       Identifiants des groupes.
 */
function groupe_plugin_lister() {

	include_spip('inc/config');
	if ($groupes = lire_config('svptype/groupes', array())) {
		$groupes = array_column($groupes, 'identifiant');
	}

	return $groupes;
}


function mot_lire_groupe($id_mot) {

	static $ids_groupe = array();

	if (!isset($ids_groupe[$id_mot])) {
		$ids_groupe[$id_mot] = 0;

		$from = 'spip_mots';
		$where = array('id_mot=' . intval($id_mot));
		$id = sql_getfetsel('id_groupe', $from, $where);
		if ($id !== null) {
			$ids_groupe[$id_mot] = intval($id);
		}
	}

	return $ids_groupe[$id_mot];
}


function mot_lire_profondeur($id_mot) {

	static $profondeurs = array();

	if (!isset($profondeurs[$id_mot])) {
		$profondeurs[$id_mot] = 0;

		$from = 'spip_mots';
		$where = array('id_mot=' . intval($id_mot));
		$profondeur = sql_getfetsel('profondeur', $from, $where);
		if ($profondeur !== null) {
			$profondeurs[$id_mot] = intval($profondeur);
		}
	}

	return $profondeurs[$id_mot];
}


function mot_lire_id($identifiant) {

	static $ids_mot = array();

	if (!isset($ids_mot[$identifiant])) {
		$ids_mot[$identifiant] = 0;

		$from = 'spip_mots';
		$where = array('identifiant=' . sql_quote($identifiant));
		$id = sql_getfetsel('id_mot', $from, $where);
		if ($id !== null) {
			$ids_mot[$identifiant] = intval($id);
		}
	}

	return $ids_mot[$identifiant];
}


function categorie_plugin_compter_affectations($categorie) {

	static $compteurs = array();
	static $id_groupe = null;

	// Déterminer l'id du groupe des catégories si il n'est pas encore stocké.
	if ($id_groupe === null) {
		include_spip('inc/config');
		$id_groupe = intval(lire_config('svptype/groupes/categories/id_groupe', 0));
	}

	// l'id du mot reflétant la catégorie si c'est une feuille ou la liste des
	// ids si c'est une catégorie de regroupement.
	if (is_string($categorie)) {
		// On a passé l'identifiant, il faut déterminer l'id du mot.
		$id_mot = mot_lire_id($categorie);
	} else {
		// On a passé l'id du mot.
		$id_mot = intval($categorie);
	}

	// Recherche des affectations de plugin. Il faut distinguer :
	// -- les catégories de regroupement comme auteur
	// -- et les catégories feuille auxquelles sont attachés les plugins (auteur/extension)
	if (!isset($compteurs[$id_mot])) {
		// Initialisation de la condition sur le groupe
		$where = array('id_groupe=' . $id_groupe);

		// Déterminer le mode de recherche suivant que la catégorie est un regroupement ou une feuille.
		$profondeur = mot_lire_profondeur($id_mot);
		if (!$profondeur) {
			// La catégorie est un regroupement, il faut établir la condition sur le parent.
			$where[] = 'id_parent=' . $id_mot;
		} else {
			// La profondeur est > 0 (forcément 1), c'est donc une feuille qui peut être affectée à un plugin.
			$where[] = 'id_mot=' . $id_mot;
		}

		$compteurs[$id_mot] = sql_countsel('spip_plugins_typologies', $where);
	}

	return $compteurs[$id_mot];
}


/**
 * Renvoie l'information brute demandée pour l'ensemble des contrôles utilisés
 * ou toute les descriptions si aucune information n'est explicitement demandée.
 *
 * @param array  $filtres
 *        Identifiant d'un champ de la description d'un contrôle.
 * @param string $information
 *        Identifiant d'un champ de la description d'un contrôle.
 *        Si l'argument est vide, la fonction renvoie les descriptions complètes et si l'argument est
 *        un champ invalide la fonction renvoie un tableau vide.
 *
 * @return array
 *        Tableau de la forme `[type_controle]  information ou description complète`. Les champs textuels
 *        sont retournés en l'état, le timestamp `maj n'est pas fourni.
 */
function type_plugin_repertorier($type, $filtres = array(), $information = '') {

	// Utilisation d'une statique pour éviter les requêtes multiples sur le même hit.
	static $categories = array();

	if (!isset($categories[$type])) {
		// On récupère l'id du groupe pour le type précisé (categorie, tag).
		include_spip('inc/config');
		$id_groupe = lire_config("svptype/groupes/${type}/id_groupe", 0);

		// On récupère la description complète de toutes les catégories de plugin
		$from = array('spip_mots');
		$where = array('id_groupe=' . $id_groupe);
		$order_by = array('identifiant');
		$categories[$type] = sql_allfetsel('*', $from, $where, '', $order_by);
	}

	// Application des filtres éventuellement demandés en argument de la fonction
	$categories_filtrees = $categories[$type];
	if ($filtres) {
		foreach ($categories_filtrees as $_categorie) {
			foreach ($filtres as $_critere => $_valeur) {
				if (isset($_description[$_critere]) and ($_categorie[$_critere] != $_valeur)) {
					unset($categories_filtrees[$_categorie]);
					break;
				}
			}
		}
	}

	if ($information) {
		$categories_filtrees = array_column($categories_filtrees, $information);
	}

    return $categories_filtrees;
}


/**
 * Renvoie l'information brute demandée pour l'ensemble des contrôles utilisés
 * ou toute les descriptions si aucune information n'est explicitement demandée.
 *
 * @param array  $filtres
 *        Identifiant d'un champ de la description d'un contrôle.
 * @param string $information
 *        Identifiant d'un champ de la description d'un contrôle.
 *        Si l'argument est vide, la fonction renvoie les descriptions complètes et si l'argument est
 *        un champ invalide la fonction renvoie un tableau vide.
 *
 * @return array
 *        Tableau de la forme `[type_controle]  information ou description complète`. Les champs textuels
 *        sont retournés en l'état, le timestamp `maj n'est pas fourni.
 */
function type_plugin_lister_affectation($type) {

	// Utilisation d'une statique pour éviter les requêtes multiples sur le même hit.
	static $affectations = array();

	if (!isset($affectations[$type])) {
		// On récupère l'id du groupe pour le type précisé (categorie, tag).
		include_spip('inc/config');
		$id_groupe = lire_config("svptype/groupes/${type}/id_groupe", 0);

		// On récupère la description complète de toutes les catégories de plugin
		$from = array('spip_plugins_typologies');
		$where = array('id_groupe=' . $id_groupe);
		$order_by = array('id_mot', 'prefixe');
		$affectations[$type] = sql_allfetsel('*', $from, $where, '', $order_by);
	}

    return $affectations[$type];
}

function categorie_plugin_importer($liste) {

	if ($liste) {
		// Récupération de l'id du groupe
		include_spip('inc/config');
		if ($id_groupe = intval(lire_config("svptype/groupes/categorie/id_groupe", 0))) {
			include_spip('action/editer_objet');
			foreach ($liste as $_categorie => $_sous_categories) {
				// On teste l'existence de la catégorie :
				// - si elle n'existe pas on la rajoute et on réserve son id,
				// - sinon on ne fait rien d'autre que de réserver l'id.
				if (!$id_categorie = mot_lire_id($_categorie)) {
					// On insère la catégorie
					$set = array(
						'identifiant' => $_categorie,
						'titre'       => $_categorie,
						'id_parent'   => 0,
					);
					$id_categorie = objet_inserer('mot', $id_groupe, $set);
				}

				// On traite maintenant les sous-catégories si on est sur que la catégorie existe
				if ($id_categorie) {
					// On insère les sous-catégories
					foreach ($_sous_categories as $_sous_categorie) {
						if (!mot_lire_id($_sous_categorie)) {
							// On insère la sous-catégorie
							$set = array(
								'identifiant' => $_sous_categorie,
								'titre'       => $_sous_categorie,
								'id_parent'   => $id_categorie,
							);
							objet_inserer('mot', $id_groupe, $set);
						}
					}
				}
			}
		}
	}
}
