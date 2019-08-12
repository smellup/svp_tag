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
 * @param string       $typologie    Typologie concernée : categorie, tag... Ne sert que si le type est passé sous forme du champ `identifiant`
 *                                   qui n'est unique qu'au sein d'une même typologie.
 * @param int|string   $type_plugin  Identifiant d'un type de plugin correspondant soit à son `id_mot` soit au champ `identifiant`.
 * @param array|string $informations Identifiant d'un champ ou de plusieurs champs de la description d'un type de plugin.
 *                                   Si l'argument est vide, la fonction renvoie la description complète.
 *
 * @return array|string
 *                      La description brute complète ou partielle du type de plugin :
 *                      - sous la forme d'une valeur simple si l'information demandée est unique (chaine)
 *                      - sous la forme d'un tableau associatif indexé par le nom du champ sinon.
 */
function type_plugin_lire($typologie, $type_plugin, $informations = array()) {
	static $description_type = array();
	static $configurations = array();

	if (!isset($description_type[$typologie][$type_plugin])) {
		// Déterminer les informations du groupe typologique si il n'est pas encore stocké.
		if (!isset($configurations[$typologie])) {
			include_spip('inc/config');
			$configurations[$typologie] = lire_config("svptype/typologies/${typologie}", array());
		}

		// Chargement de la description nécessaire du type de plugin en base de données.
		// -- seules l'id, l'id_parent, la profondeur, l'identifiant typologique, le titre et le descriptif sont utiles.
		$champs_type_plugin = array('id_mot', 'id_parent', 'identifiant', 'profondeur', 'titre', 'descriptif');
		// -- on construit la condition soit sur l'id_mot soit sur l'identifiant en fonction de ce qui est passé
		//    dans le paramètre $type_plugin.
		if ($id_mot = intval($type_plugin)) {
			$where = array(
				'id_mot=' . $id_mot,
			);
		} else {
			$where = array(
				'id_groupe=' . intval($configurations[$typologie]['id_groupe']),
				'identifiant=' . sql_quote($type_plugin)
			);
		}
		$description = sql_fetsel($champs_type_plugin, 'spip_mots', $where);

		// Sauvegarde de la description de la page pour une consultation ultérieure dans le même hit.
		if ($description) {
			// Traitements des champs entiers id et profondeur
			$description['id_mot'] = intval($description['id_mot']);
			$description['id_parent'] = intval($description['id_parent']);
			$description['profondeur'] = intval($description['profondeur']);

			// Stockage de la description
			$description_type[$typologie][$type_plugin] = $description;
		} else {
			// En cas d'erreur stocker une description vide
			$description_type[$typologie][$type_plugin] = false;
		}
	}

	// On ne retourne que les champs demandés
	$type_plugin_lu = $description_type[$typologie][$type_plugin];
	if ($type_plugin_lu and $informations) {
		// Extraction des seules informations demandées.
		// -- si on demande une information unique on renvoie la valeur simple, sinon on renvoie un tableau.
		// -- si une information n'est pas un champ valide elle n'est pas renvoyée sans monter d'erreur.
		if (is_array($informations)) {
			if (count($informations) == 1) {
				// Tableau d'une seule information : on revient à une chaine unique.
				$informations = array_shift($informations);
			} else {
				// Tableau des informations valides
				$type_plugin_lu = array_intersect_key($type_plugin_lu, array_flip($informations));
			}
		}

		if (is_string($informations)) {
			// Valeur unique demandée.
			$type_plugin_lu = isset($description_type[$typologie][$type_plugin][$informations])
				? $description_type[$typologie][$type_plugin][$informations]
				: '';
		}
	}

	return $type_plugin_lu;
}

/**
 * Renvoie l'information brute demandée pour l'ensemble des types de plugins d'une typologie donnée
 * ou toute les descriptions si aucune information n'est explicitement demandée.
 *
 * @api
 *
 * @param string $typologie    Typologie concernée : categorie, tag...
 * @param array  $filtres      Liste des couples (champ, valeur) ou tableau vide.
 * @param array  $informations Identifiant d'un champ ou de plusieurs champs de la description d'un type de plugin.
 *                             Si l'argument est vide, la fonction renvoie les descriptions complètes.
 *
 * @return array Description complète ou information précise pour chaque type de plugin de la typologie concernée.
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
 * @param string $typologie Typologie concernée : categorie, tag...
 * @param array  $filtres   Liste des couples (champ, valeur) ou tableau vide.
 *                          Pratiquement, les critères admins sont `prefixe`, `id_mot` et aussi `type` qui revient à filtrer
 *                          sur un type de plugin comme id_mot.
 *
 * @return array Description de chaque affectation (type de plugin, plugin) de la typologie concernée.
 */
function type_plugin_repertorier_affectation($typologie, $filtres = array()) {

	// On récupère l'id du groupe pour la typologie concernée.
	include_spip('inc/config');
	$id_groupe = lire_config("svptype/typologies/${typologie}/id_groupe", 0);

	// On initialise la jointure pour récupérer l'identifiant du type de plugin et pas uniquement son id.
	$from = array('spip_plugins_typologies', 'spip_mots');
	$select = array(
		'spip_plugins_typologies.id_groupe',
		'spip_plugins_typologies.id_mot',
		'spip_mots.identifiant as identifiant_mot',
		'spip_plugins_typologies.prefixe'
	);
	$order_by = array('spip_plugins_typologies.id_mot', 'spip_plugins_typologies.prefixe');

	// On calcule les conditions en y intégrant les critères si ils existent
	// -- conditions minimales
	$where = array(
		'spip_plugins_typologies.id_groupe=' . $id_groupe,
		'spip_plugins_typologies.id_mot=spip_mots.id_mot'
	);
	// -- conditions issues des filtres
	if ($filtres) {
		// Traitement du cas où le critère 'type' est utilisé : on le transforme en un critère id_mot.
		if (isset($filtres['type'])) {
			$id_mot = type_plugin_lire($typologie, $filtres['type'], 'id_mot');
			unset($filtres['type']);
			$filtres['id_mot'] = $id_mot;
		}

		// on traite maintenant tous les filtres
		foreach ($filtres as $_critere => $_valeur) {
			if ($_critere == 'id_mot') {
				$where[] = "spip_plugins_typologies.${_critere}=" . intval($_valeur);
			} elseif ($_critere == 'prefixe') {
				$where[] = "spip_plugins_typologies.${_critere}=" . sql_quote(strtoupper($_valeur));
			}
		}
	}

	// Récupération des affectations.
	$affectations = sql_allfetsel($select, $from, $where, '', $order_by);

	return $affectations;
}

/**
 * Dénombre les types de plugin enfants d'un type d'une typologie donnée.
 *
 * @api
 *
 * @param string     $typologie   Typologie concernée : categorie, tag...
 * @param int|string $type_plugin Identifiant d'un type de plugin correspondant soit à son `id_mot` soit au champ `identifiant`.
 *
 * @return int Nombre d'enfants d'un type de plugin ou 0 si aucun.
 */
function type_plugin_compter_enfant($typologie, $type_plugin) {

	// Initialisations statiques pour les performances.
	static $compteurs = array();

	// Le type est fourni soit sous forme de son identifiant soit de son id.
	// On calcule dans tous les cas l'id.
	if (!$id_mot = intval($type_plugin)) {
		// On a passé l'identifiant, il faut déterminer l'id du mot.
		$id_mot = type_plugin_lire($typologie, $type_plugin, 'id_mot');
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
 * @param string     $typologie   Typologie concernée : categorie, tag...
 * @param int|string $type_plugin Identifiant d'un type de plugin correspondant soit à son `id_mot` soit au champ `identifiant`.
 *
 * @return int Nombre d'affectations (type de plugin, plugin) d'un type de plugin ou 0 si aucun.
 */
function type_plugin_compter_affectation($typologie, $type_plugin) {

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
	$description_type = type_plugin_lire($typologie, $type_plugin, array('id_mot', 'profondeur'));
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
