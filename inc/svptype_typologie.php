<?php
/**
 * Ce fichier contient l'API de gestion des différentes typologie de plugin.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Renvoie l'information brute demandée pour l'ensemble des types concernés
 * ou toute les descriptions si aucune information n'est explicitement demandée.
 *
 * @param string $typologie
 *        Typologie concernée : categorie ou tag.
 * @param array  $filtres
 *        Identifiant d'un champ de la description d'un contrôle.
 * @param string $information
 *        Identifiant d'un champ de la description d'un contrôle.
 *        Si l'argument est vide, la fonction renvoie les descriptions complètes et si l'argument est
 *        un champ invalide la fonction renvoie un tableau vide.
 *
 * @return array
 *        Tableau de la forme `[type_controle]  information ou description complète`.
 */
function type_plugin_repertorier($typologie, $filtres = array(), $information = '') {

	// Utilisation d'une statique pour éviter les requêtes multiples sur le même hit.
	static $types = array();

	if (!isset($types[$typologie])) {
		// On récupère l'id du groupe pour le type précisé (categorie, tag).
		include_spip('inc/config');
		$id_groupe = lire_config("svptype/groupes/${typologie}/id_groupe", 0);

		// On récupère la description complète de toutes les catégories de plugin
		$from = array('spip_mots');
		$where = array('id_groupe=' . $id_groupe);
		$order_by = array('identifiant');
		$types[$typologie] = sql_allfetsel('*', $from, $where, '', $order_by);
	}

	// Application des filtres éventuellement demandés en argument de la fonction
	$types_filtrees = $types[$typologie];
	if ($filtres) {
		foreach ($types_filtrees as $_categorie) {
			foreach ($filtres as $_critere => $_valeur) {
				if (isset($_description[$_critere]) and ($_categorie[$_critere] != $_valeur)) {
					unset($types_filtrees[$_categorie]);
					break;
				}
			}
		}
	}

	if ($information) {
		$types_filtrees = array_column($types_filtrees, $information);
	}

    return $types_filtrees;
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
function type_plugin_lister_affectation($typologie) {

	// Utilisation d'une statique pour éviter les requêtes multiples sur le même hit.
	static $affectations = array();

	if (!isset($affectations[$typologie])) {
		// On récupère l'id du groupe pour le type précisé (categorie, tag).
		include_spip('inc/config');
		$id_groupe = lire_config("svptype/groupes/${typologie}/id_groupe", 0);

		// On récupère la description complète de toutes les catégories de plugin
		$from = array('spip_plugins_typologies');
		$where = array('id_groupe=' . $id_groupe);
		$order_by = array('id_mot', 'prefixe');
		$affectations[$typologie] = sql_allfetsel('*', $from, $where, '', $order_by);
	}

    return $affectations[$typologie];
}


/**
 * Importe une liste de catégories.
 *
 * @param array  $liste
 *        Tableau des catégories présenté comme une arborescence.
 *
 * @return bool|int
 *         Nombre de catégories ajoutées.
 */
function categorie_plugin_importer_liste($liste) {

	// Initialisation du nombre de catégories ajoutées.
	$categories_ajoutees = 0;

	if ($liste) {
		// Récupération de l'id du groupe
		include_spip('inc/config');
		if ($id_groupe = intval(lire_config("svptype/groupes/categorie/id_groupe", 0))) {
			// Identification des champs acceptables pour une catégorie
			include_spip('base/objets');
			$description_table = lister_tables_objets_sql('spip_mots');
			$champs = $description_table['field'];

			include_spip('action/editer_objet');
			include_spip('inc/svptype_mot');
			foreach ($liste as $_regroupement) {
				// On teste l'existence de la catégorie de regroupement :
				// - si elle n'existe pas on la rajoute,
				// - sinon on ne fait rien.
				// Dans tous les cas, on réserve l'id.
				if (!$id_regroupement = mot_lire_id($_regroupement['identifiant'])) {
					// On insère la catégorie de regroupement qui est une racine (id_parent à 0).
					$set = array_intersect_key($_regroupement, $champs);
					$set['id_parent'] = 0;
					$id_regroupement = objet_inserer('mot', $id_groupe, $set);
				}

				// On traite maintenant les sous-catégories si on est sur que la catégorie de regroupement existe
				if ($id_regroupement) {
					// Enregistrement de la catégorie ajoutée
					$categories_ajoutees += 1;

					// On insère les catégories si elles ne sont pas déjà présentes dans la base.
					foreach ($_regroupement['categories'] as $_categorie) {
						if (!mot_lire_id($_categorie['identifiant'])) {
							// On insère la catégorie feuille sous son parent.
							$set = array_intersect_key($_categorie, $champs);
							$set['id_parent'] = $id_regroupement;
							if (objet_inserer('mot', $id_groupe, $set)) {
								// Enregistrement de la catégorie ajoutée
								$categories_ajoutees += 1;
							}
						}
					}
				}
			}
		}
	}

	return $categories_ajoutees;
}


/**
 * Importe une liste d'affectation catégorie-plugin.
 *
 * @param array  $liste
 *        Tableau des catégories présenté comme une arborescence.
 *
 * @return bool|int
 *         Nombre d'affectations ajoutées.
 */
function categorie_plugin_importer_affectation($liste) {

	// Initialisation du nombre d'affectations catégorie-plugin ajoutées.
	$affectations_ajoutees = 0;

	if ($liste) {
		// Récupération de l'id du groupe
		include_spip('inc/config');
		if ($id_groupe = intval(lire_config("svptype/groupes/categorie/id_groupe", 0))) {
			// Initialisation d'un enregistrement
			$set = array(
				'id_groupe' => $id_groupe
			);

			include_spip('inc/svptype_mot');
			foreach ($liste as $_affectation) {
				// On teste l'existence de la catégorie désignée par son identifiant en récupérant son id_mot.
				if (!empty($_affectation['categorie'])
				and !empty($_affectation['prefixe'])
				and ($id_mot = mot_lire_id($_affectation['categorie']))) {
					// On teste l'existence de l'affectation :
					// - si elle n'existe pas on la rajoute,
					// - sinon on ne fait rien car i ne peut y avoir qu'une seule affectation par préfixe.
					$where = array(
						'id_mot=' . $id_mot,
						'prefixe=' . sql_quote($_affectation['prefixe'])
					);
					if (!sql_countsel('spip_plugins_typologies', $where)) {
						$set['id_mot'] = $id_mot;
						$set['prefixe'] = $_affectation['prefixe'];

						if (sql_insertq('spip_plugins_typologies', $set)) {
							// Enregistrement de la catégorie ajoutée
							$affectations_ajoutees += 1;
						}
					}
				}
			}
		}
	}

	return $affectations_ajoutees;
}


function categorie_plugin_compter_affectations($categorie) {

	// Initialisations statiques pour les performances.
	static $compteurs = array();
	static $id_groupe = null;

	// Déterminer l'id du groupe des catégories si il n'est pas encore stocké.
	if ($id_groupe === null) {
		include_spip('inc/config');
		$id_groupe = intval(lire_config('svptype/groupes/categories/id_groupe', 0));
	}

	// l'id du mot reflétant la catégorie si c'est une feuille ou la liste des
	// ids si c'est une catégorie de regroupement.
	include_spip('inc/svptype_mot');
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
