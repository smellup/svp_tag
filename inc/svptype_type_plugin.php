<?php
/**
 * Ce fichier contient l'API de gestion des types de plugin.
 *
 * @package SPIP\SVPTYPE\TYPE_PLUGIN\API
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Retourne la description complète du type de plugin ou uniquement une information précise.
 *
 * @api
 *
 * @param string     $typologie
 *        Typologie concernée : categorie, tag... Ne sert que si le type est passé sous forme du champ `identifiant`
 *        qui n'est unique qu'au sein d'une même typologie.
 * @param int|string $type
 *        Identifiant d'un type de plugin correspondant soit à son `id_mot` soit au champ `identifiant`.
 * @param string     $information
 *        Champ spécifique à retourner ou vide pour retourner toute la description.
 *
 * @return array|string
 *        La description complète ou un champ précis demandé pour une page donnée. Les champs
 *        de type entier sont traités avec la fonction intval() avant d'être fournis.
 */
function type_plugin_lire($typologie, $type, $information = '') {

	static $description_type = array();
	static $configurations = array();

	if (!isset($description_type[$typologie][$type])) {
		// Déterminer les informations du groupe typologique si il n'est pas encore stocké.
		if (!isset($configurations[$typologie])) {
			include_spip('inc/config');
			$configurations[$typologie] = lire_config("svptype/typologies/${typologie}", array());
		}

		// Chargement de la description nécessaire du type de plugin en base de données.
		// -- seules l'id, l'id_parent, la profondeur, l'identifiant typologique, le titre et le descriptif sont utiles.
		$select = array('id_mot', 'id_parent', 'identifiant', 'profondeur', 'titre', 'descriptif');
		// -- on construit la condition soit sur l'id_mot soit sur l'identifiant en fonction de ce qui est passé
		//    dans le paramètre $type.
		if ($id_mot = intval($type)) {
			$where = array(
				'id_mot=' . $id_mot,
			);
		} else {
			$where = array(
				'id_groupe=' . intval($configurations[$typologie]['id_groupe']),
				'identifiant=' . sql_quote($type)
			);
		}
		$description = sql_fetsel($select, 'spip_mots', $where);

		// Sauvegarde de la description de la page pour une consultation ultérieure dans le même hit.
		if ($description) {
			// Traitements des champs entiers id et profondeur
			$description['id_mot'] = intval($description['id_mot']);
			$description['id_parent'] = intval($description['id_parent']);
			$description['profondeur'] = intval($description['profondeur']);

			// Stockage de la description
			$description_type[$typologie][$type] = $description;
		} else {
			// En cas d'erreur stocker une description vide
			$description_type[$typologie][$type] = array();
		}
	}

	if ($information) {
		if (isset($description_type[$typologie][$type][$information])) {
			$type_lu = $description_type[$typologie][$type][$information];
		} else {
			$type_lu = null;
		}
	} else {
		$type_lu = $description_type[$typologie][$type];
	}

	return $type_lu;
}


/**
 * Renvoie l'information brute demandée pour l'ensemble des types de plugins d'une typologie donnée
 * ou toute les descriptions si aucune information n'est explicitement demandée.
 *
 * @api
 *
 * @param string $typologie
 *        Typologie concernée : categorie, tag...
 * @param array  $filtres
 *        Liste des couples (champ, valeur) ou tableau vide.
 * @param array  $informations
 *        Identifiant d'un champ ou de plusieurs champs de la description d'un type de plugin.
 *        Si l'argument est vide, la fonction renvoie les descriptions complètes.
 *
 * @return array
 *        Description complète ou information précise pour chaque type de plugin de la typologie concernée.
 */
function type_plugin_repertorier($typologie, $filtres = array(), $informations = array()) {

	// Utilisation d'une statique pour éviter les requêtes multiples sur le même hit.
	static $types = array();

	if (!isset($types[$typologie])) {
		// On récupère l'id du groupe pour le type précisé (categorie, tag).
		include_spip('inc/config');
		$id_groupe = lire_config("svptype/typologies/${typologie}/id_groupe", 0);

		// On récupère la description complète de toutes les catégories de plugin
		$from = array('spip_mots');
		$where = array('id_groupe=' . $id_groupe);
		$order_by = array('identifiant');
		$types[$typologie] = sql_allfetsel('*', $from, $where, '', $order_by);
	}

	// Refactoring du tableau suivant les champs demandés et application des filtres.
	$types_filtrees = array();
	$informations = $informations ? array_flip($informations) : array();
	foreach ($types[$typologie] as $_cle => $_type) {
		// On détermine si on retient ou pas le type.
		$filtre_ok = true;
		foreach ($filtres as $_critere => $_valeur) {
			if (isset($_type[$_critere]) and ($_type[$_critere] != $_valeur)) {
				$filtre_ok = false;
				break;
			}
		}

		// Ajout du type si le filtre est ok.
		if ($filtre_ok) {
			$types_filtrees[] = $informations
				? array_intersect_key($types[$typologie][$_cle], $informations)
				: $types[$typologie][$_cle];
		}
	}

    return $types_filtrees;
}


/**
 * Renvoie les affectations (type de plugin, plugin) pour une typologie donnée.
 *
 * @api
 *
 * @param string     $typologie
 *        Typologie concernée : categorie, tag...
 * @param int|string $type
 *        Identifiant d'un type de plugin correspondant soit à son `id_mot` soit au champ `identifiant`.
 *
 * @return array
 *        Description de chaque affectation (type de plugin, plugin) de la typologie concernée.
 */
function type_plugin_repertorier_affectation($typologie, $type = '') {

	// Utilisation d'une statique pour éviter les requêtes multiples sur le même hit.
	static $affectations = array();

	if (!isset($affectations[$typologie])) {
		// On récupère l'id du groupe pour la typologie concernée.
		include_spip('inc/config');
		$id_groupe = lire_config("svptype/typologies/${typologie}/id_groupe", 0);

		// On récupère la description complète de toutes les types de plugin de la typologies concernée.
		$from = array('spip_plugins_typologies', 'spip_mots');
		$select = array(
			'spip_plugins_typologies.id_groupe',
			'spip_plugins_typologies.id_mot',
			'spip_mots.identifiant as identifiant_mot',
			'spip_plugins_typologies.prefixe'
		);
		$where = array(
			'spip_plugins_typologies.id_groupe=' . $id_groupe,
			'spip_plugins_typologies.id_mot=spip_mots.id_mot'
		);
		$order_by = array('spip_plugins_typologies.id_mot', 'spip_plugins_typologies.prefixe');
		$affectations[$typologie] = sql_allfetsel($select, $from, $where, '', $order_by);
	}

	// Filtrer sur le type souhaité si il existe.
	if (!$type) {
		$affectations_filtrees = $affectations[$typologie];
	} else {
		// Récupération de l'id du type suivant la nature de l'argument $type.
		if (!$id_mot = intval($type)) {
			$id_mot = type_plugin_lire($typologie, $type, 'id_mot');
		}

		// Extraction des seules affectations au type.
		$affectations_filtrees = array();
		foreach ($affectations[$typologie] as $_affectation) {
			if ($_affectation['id_mot'] == $id_mot) {
				$affectations_filtrees[] = $_affectation;
			}
		}
	}

    return $affectations_filtrees;
}


/**
 * Dénombre les types de plugin enfants d'un type d'une typologie donnée.
 *
 * @api
 *
 * @param string     $typologie
 *        Typologie concernée : categorie, tag...
 * @param int|string $type
 *        Identifiant d'un type de plugin correspondant soit à son `id_mot` soit au champ `identifiant`.
 *
 * @return int
 *         Nombre d'enfants d'un type de plugin ou 0 si aucun.
 */
function type_plugin_compter_enfant($typologie, $type) {

	// Initialisations statiques pour les performances.
	static $compteurs = array();

	// Le type est fourni soit sous forme de son identifiant soit de son id.
	// On calcule dans tous les cas l'id.
	if (!$id_mot = intval($type)) {
		// On a passé l'identifiant, il faut déterminer l'id du mot.
		$id_mot = type_plugin_lire($typologie, $type, 'id_mot');
	}

	// On acquiert les enfants éventuels du type et on en calcule le nombre.
	if (!isset($compteurs[$id_mot])) {
		include_spip('inc/svptype_mot');
		$compteurs[$id_mot] = count(mot_lire_enfants($id_mot));
	}

	return $compteurs[$id_mot];
}


/**
 * Dénombre les affectations (type de plugin, plugin) d'un type d'une typologie.
 *
 * @api
 *
 * @param string     $typologie
 *        Typologie concernée : categorie, tag...
 * @param int|string $type
 *        Identifiant d'un type de plugin correspondant soit à son `id_mot` soit au champ `identifiant`.
 *
 * @return int
 *         Nombre d'affectations (type de plugin, plugin) d'un type de plugin ou 0 si aucun.
 */
function type_plugin_compter_affectation($typologie, $type) {

	// Initialisations statiques pour les performances.
	static $compteurs = array();
	static $configurations_typologie = array();

	// Déterminer les informations du groupe typologique si il n'est pas encore stocké.
	if (!isset($configurations_typologie[$typologie])) {
		include_spip('inc/config');
		$configurations_typologie[$typologie] = lire_config("svptype/typologies/${typologie}", array());
	}

	// Le type est fourni soit sous forme de son identifiant soit de son id.
	// Extrait les informations du type de plugin pour utiliser id_mot et profondeur.
	$description_type = type_plugin_lire($typologie, $type);
	$id_mot = $description_type['id_mot'];

	// Recherche des affectations de plugin. Pour les catégories qui sont arborescentes, il faut distinguer :
	// -- les catégories de regroupement comme auteur
	// -- et les catégories feuille auxquelles sont attachés les plugins (auteur/extension)
	if (!isset($compteurs[$id_mot])) {
		// Initialisation de la condition sur le groupe de mots.
		$where = array('id_groupe=' . intval($configurations_typologie[$typologie]['id_groupe']));

		// Déterminer le mode de recherche suivant que :
		// - la typologie est arborescente ou pas
		// - le type est une racine ou une feuille.
		if ($configurations_typologie[$typologie]['est_arborescente']
		and ($description_type['profondeur'] == 0)) {
			// La typologie est arborescente et le type est une racine, il faut établir la condition sur les mots
			// feuille de cette racine.
			// -- On recherche les id_mot des feuilles de la racine
			include_spip('inc/svptype_mot');
			$ids_enfant = mot_lire_enfants($id_mot);
			$where[] = sql_in('id_mot', $ids_enfant);
		} else {
			// La profondeur est > 0, c'est donc une feuille qui peut être affectée à un plugin : on étabit la condition
			// sur le mot lui-même.
			$where[] = 'id_mot=' . $id_mot;
		}

		$compteurs[$id_mot] = sql_countsel('spip_plugins_typologies', $where);
	}

	return $compteurs[$id_mot];
}
