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
 * @param array  $informations
 *        Identifiant d'un champ ou plusieurs champs de la description d'un type de plugin.
 *        Si l'argument est vide, la fonction renvoie les descriptions complètes.
 *
 * @return array
 *        Tableau de la forme `[type_controle]  information ou description complète`.
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
 * Renvoie les affectations aux plugins pour une typologie donnée.
 *
 * @param string $typologie
 *        Typologie concernée : categorie ou tag.
 * @param string $type
 *        Valeur d'un type donné pour la typologie concernée.
 *
 * @return array
 */
function type_plugin_lister_affectation($typologie, $type = '') {

	// Utilisation d'une statique pour éviter les requêtes multiples sur le même hit.
	static $affectations = array();

	if (!isset($affectations[$typologie])) {
		// On récupère l'id du groupe pour le type précisé (categorie, tag).
		include_spip('inc/config');
		$id_groupe = lire_config("svptype/typologies/${typologie}/id_groupe", 0);

		// On récupère la description complète de toutes les catégories de plugin
		$from = array('spip_plugins_typologies');
		$where = array('id_groupe=' . $id_groupe);
		$order_by = array('id_mot', 'prefixe');
		$affectations[$typologie] = sql_allfetsel('*', $from, $where, '', $order_by);
	}

	// Filtrer sur le type souhaité si il existe.
	if (!$type) {
		$affectations_filtrees = $affectations[$typologie];
	} else {
		// Récupération de l'id du type
		include_spip('inc/svptype_mot');
		$id_type = mot_lire_id($type);

		// Extraction des seules affectations au type.
		$affectations_filtrees = array();
		foreach ($affectations[$typologie] as $_affectation) {
			if ($_affectation['id_mot'] == $id_type) {
				$affectations_filtrees[] = $_affectation;
			}
		}
	}

    return $affectations_filtrees;
}


/**
 * Importe une liste de types appartenant à la même typologie.
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
		if ($id_groupe = intval(lire_config('svptype/typologies/categorie/id_groupe', 0))) {
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
					++$categories_ajoutees;

					// On insère les catégories si elles ne sont pas déjà présentes dans la base.
					foreach ($_regroupement['sous-types'] as $_categorie) {
						if (!mot_lire_id($_categorie['identifiant'])) {
							// On insère la catégorie feuille sous son parent.
							$set = array_intersect_key($_categorie, $champs);
							$set['id_parent'] = $id_regroupement;
							if (objet_inserer('mot', $id_groupe, $set)) {
								// Enregistrement de la catégorie ajoutée
								++$categories_ajoutees;
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
 * Importe une liste d'affectation type-plugin pour une typologie donnée.
 * Le format du fichier est indépendant de la typologie.
 *
 * @param string $typologie
 *        Typologie concernée : categorie ou tag.
 * @param array  $affectations
 *        Tableau des affectations type-plugin (agnostique vis-à-vis de la typologie).
 *
 * @return int
 *         Nombre d'affectations ajoutées.
 */
function type_plugin_importer_affectation($typologie, $affectations) {

	// Initialisation du nombre d'affectations catégorie-plugin ajoutées.
	$nb_affectations_ajoutees = 0;

	if ($affectations) {
		// Déterminer les informations du groupe typologique.
		include_spip('inc/config');
		$groupe = lire_config("svptype/typologies/${typologie}", array());

		if ($id_groupe = intval($groupe['id_groupe'])) {
			// Initialisation d'un enregistrement d'affectation.
			$set = array(
				'id_groupe' => $id_groupe
			);

			include_spip('inc/svptype_mot');
			foreach ($affectations as $_affectation) {
				// On contrôle tout d'abord que l'affectation est correcte :
				// -- type et préfixe sont renseignés,
				// -- le type existe dans la base.
				if (!empty($_affectation['type'])
				and !empty($_affectation['prefixe'])
				and ($id_mot = mot_lire_id($_affectation['type']))) {
					// On vérifie que l'affectation n'existe pas déjà pour la typologie.
					$where = array(
						'id_mot=' . $id_mot,
						'prefixe=' . sql_quote($_affectation['prefixe'])
					);
					if (!sql_countsel('spip_plugins_typologies', $where)) {
						// In fine, on vérifie que le nombre maximal d'affectations pour un plugin n'est pas atteint
						// pour la typologie.
						$where = array(
							'prefixe=' . sql_quote($_affectation['prefixe']),
							'id_groupe=' . $id_groupe
						);
						if (!$groupe['max_affectations']
						or (sql_countsel('spip_plugins_typologies', $where) < $groupe['max_affectations'])) {
							// On peut insérer la nouvelle affectation
							$set['id_mot'] = $id_mot;
							$set['prefixe'] = $_affectation['prefixe'];
							if (sql_insertq('spip_plugins_typologies', $set)) {
								// Enregistrement de l'ajout de l'affectation.
								++$nb_affectations_ajoutees;
							}
						}
					}
				}
			}
		}
	}

	return $nb_affectations_ajoutees;
}


function type_plugin_compter_affectations($typologie, $type) {

	// Initialisations statiques pour les performances.
	static $compteurs = array();
	static $groupes = array();

	// Déterminer les informations du groupe typologique si il n'est pas encore stocké.
	if (!isset($groupes[$typologie])) {
		include_spip('inc/config');
		$groupes[$typologie] = lire_config("svptype/typologies/${typologie}", array());
	}

	// Le type est fourni soit sous forme de son identifiant soit de son id.
	// On calcule dans tous les cas l'id.
	include_spip('inc/svptype_mot');
	if (!$id_mot = intval($type)) {
		// On a passé l'identifiant, il faut déterminer l'id du mot.
		$id_mot = mot_lire_id($type);
	}

	// Recherche des affectations de plugin. Pour les catégories qui sont arborescentes, il faut distinguer :
	// -- les catégories de regroupement comme auteur
	// -- et les catégories feuille auxquelles sont attachés les plugins (auteur/extension)
	if (!isset($compteurs[$id_mot])) {
		// Initialisation de la condition sur le groupe de mots.
		$where = array('id_groupe=' . intval($groupes[$typologie]['id_groupe']));

		// Déterminer le mode de recherche suivant que :
		// - la typologie est arborescente ou pas
		// - le type est une racine ou une feuille.
		$profondeur = mot_lire_profondeur($id_mot);
		if (($groupes[$typologie]['mots_arborescents'] == 'oui')
		and ($profondeur == 0)) {
			// La typologie est arborescente et le type est une racine, il faut établir la condition sur les mots
			// feuille de cette racine.
			// -- On recherche les id_mot des feuilles de la racine
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


function type_plugin_construire_selection($typologie, $options = array()) {

	// Déterminer les informations du groupe typologique.
	include_spip('inc/config');
	$groupe = lire_config("svptype/typologies/${typologie}", array());

	// Vérification des options.
	if ($groupe['mots_arborescents'] == 'non') {
		// Seule l'option du titre est acceptée pour cette typologie car les types ne sont pas arborescents.
		$options['niveau_affiche'] = '';
		$options['optgroup'] = '';
	} else {
		// Pour les typologies arborescentes, il faut sélectionner les deux niveaux pour que l'option optgroup
		// ait un sens.
		if (!empty($options['niveau_affiche'])) {
			$options['optgroup'] = '';
		}
	}

	// Calcul du where en fonction des options :
	// - 'niveau_affiche' : si vide, on affiche tout, sinon on affiche un niveau donné.
	$where = array('id_groupe=' . intval($groupe['id_groupe']));
	if (!empty($options['niveau_affiche'])) {
		$where[] = 'profondeur=' . ($options['niveau_affiche'] == 'racine' ? 0 : 1);
	}

	// Calcul du select en fonction des options :
	// - 'option_titre' : si vide on affiche l'identifiant, sinon le titre du type.
	$select = array('identifiant', 'profondeur');
	if (!empty($options['titre_affiche'])) {
		$select[] = 'titre';
	}

	// On récupère l'identifiant et éventuellement le titre des catégories de plugin requises uniquement.
	$from = array('spip_mots');
	$order_by = array('identifiant');
	$types = sql_allfetsel($select, $from, $where, '', $order_by);

	// On formate le tableau en fonction des options.
	// -- L'option optgroup a déjà été vérifiée : si elle est encore active, on est en présence
	//    d'une arborescence de types à présenter avec optgroup.
	//    Sinon, on veut une liste aplatie classée alphabétiquement.
	if (!empty($options['optgroup'])) {
		// On retraite le tableau en arborescence conformément à ce qui est attendu par la saisie selection avec
		// optgroup.
		$data = $groupes = array();
		// On initialise les groupes et on réserve l'index afin de pouvoir les reconnaitre lors de la prochaine boucle.
		foreach ($types as $_type) {
			if ($_type['profondeur'] == 0) {
				$index = empty($options['titre_affiche']) ? $_type['identifiant'] : $_type['titre'];
				$data[$index] = array();
				$groupes[$_type['identifiant']] = $index;
			}
		}
		// On ajoute ensuite les enfants dans le groupe parent.
		foreach ($types as $_type) {
			if ($_type['profondeur'] == 1) {
				// Extraction de l'identifiant du groupe (groupe/xxxx)
				$identifiants = explode('/', $_type['identifiant']);
				$index = $groupes[$identifiants[0]];
				$data[$index][$_type['identifiant']] = empty($options['titre_affiche'])
					? $_type['identifiant']
					: $_type['titre'];
			}
		}
	} else {
		// Si on ne veut pas de optgroup, on liste les types dans l'ordre alphabétique.
		// Seule l'option du titre est à considérer.
		$data = !empty($options['titre_affiche'])
			? array_column($types, 'titre', 'identifiant')
			: array_column($types, 'identifiant', 'identifiant');
	}

    return $data;
}


function type_plugin_construire_filtre($type) {

	$filtre = '';

	if ($type) {
		// On détermine l'id et la profondeur du type.
		include_spip('inc/svptype_mot');
		if ($id = mot_lire_id($type)) {
			// On détermine la profondeur du type qui est plus fiable que de tester l'existence d'un "/".
			$profondeur = mot_lire_profondeur($id);

			if ($profondeur == 1) {
				// Le type est un enfant, on filtre sur son id.
				$filtre = 'plugins_typologies.id_mot=' . $id;
			} else {
				// Le type est une racine, on filtre en utilisant son id comme un parent.
				$filtre = 'id_parent=' . $id;
			}
		}
	}

	return $filtre;
}
