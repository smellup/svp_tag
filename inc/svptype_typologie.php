<?php
/**
 * Ce fichier contient l'API de gestion des typologies de plugin.
 *
 * @package SPIP\SVPTYPE\TYPOLOGIE_PLUGIN\API
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Initialise la configuration des différentes typologies de plugin proposées.
 * Cette configuration sert à initialiser l'index `typologies` de la meta `svptype`.
 *
 * @api
 *
 * @return array
 *        Le tableau de la configuration par défaut indexé par l'identifiant de chaque typologie.
 */
function typologie_plugin_configurer() {

	// Deux typologies actuellement :
	// - categorie : les catégories de plugin
	// - tag : les tags de plugin
	$configurations_typologie = array(
		'categorie' => array(
			'est_arborescente' => true,
			'id_groupe'        => 0,
			'max_affectations' => 1,
			'max_profondeur'   => 1,
			'collection'       => array(
				'nom'       => 'categories',
				'module'    => 'svptype',
				'filtres'   => array(
					array(
						'critere'         => 'profondeur',
						'est_obligatoire' => false
					)
				)
			)
		),
		'tag'       => array(
			'est_arborescente' => false,
			'id_groupe'        => 0,
			'max_affectations' => 0,
			'max_profondeur'   => 0,
			'collection'       => array(
				'nom'       => 'tags',
				'module'    => 'svptype',
				'filtres'   => array()
			)
		),
	);

	return $configurations_typologie;
}


/**
 * Création des groupes de mots matérialisant chaque typologie de plugin.
 * Si le groupe existe déjà on ne fait rien, sinon on le crée en stockant l'id du groupe obtenu dans la configuration
 * idoine.
 *
 * @api
 *
 * @return void
 */
function typologie_plugin_creer_groupe() {

	// Les groupes de typologie de plugin ont les caractéristiques communes suivantes :
	// - groupe technique
	// - sans tables liées
	// - et uniquement pour les administrateurs complets.

	// On acquiert la configuration déjà enregistrée pour le plugin.
	include_spip('inc/config');
	$configuration_plugin = lire_config('svptype', array());

	if (!empty($configuration_plugin['typologies'])) {
		include_spip('action/editer_objet');
		$configuration_plugin_modifiee = false;
		foreach ($configuration_plugin['typologies'] as $_typologie => $_configuration_typologie) {
			// On vérifie d'abord si le groupe existe déjà. Si oui, on ne fait rien.
			if (!$_configuration_typologie['id_groupe']) {
				$groupe = array(
					'titre'             => "typologie-${_typologie}-plugin",
					'technique'         => 'oui',
					'mots_arborescents' => $_configuration_typologie['est_arborescente'] ? 'oui' : 'non',
					'tables_liees'      => '',
					'minirezo'          => 'oui',
					'comite'            => 'non',
					'forum'             => 'non',
				);
				if ($id_groupe = objet_inserer('groupe_mots', null, $groupe)) {
					$configuration_plugin['typologies'][$_typologie]['id_groupe'] = $id_groupe;
					$configuration_plugin_modifiee = true;
				} else {
					spip_log(
						"Erreur lors de l'ajout du groupe pour la typologie ${_typologie}",
						'svptype' . _LOG_ERREUR
					);
				}
			}
		}

		// Ecriture de la configuration mise à jour
		if ($configuration_plugin_modifiee) {
			ecrire_config('svptype', $configuration_plugin);
		}
	}
}


/**
 * Importe une liste de types de plugin appartenant à une même typologie.
 * Les types de plugin de la liste déjà présents en base de données sont ignorés.
 *
 * @api
 *
 * @param string $typologie
 *        Identifiant de la typologie concernée : categorie, tag...
 * @param array  $types
 *        Tableau des types présenté comme une arborescence ou à plat suivant la typologie.
 *
 * @return bool|int
 *         Nombre de catégories ajoutées.
 */
function typologie_plugin_importer($typologie, $types) {

	// Initialisation du nombre de types ajoutés.
	$types_ajoutes = 0;

	if ($types) {
		// Acquérir la configuration de la typologie.
		include_spip('inc/config');
		$config_typologie = lire_config("svptype/typologies/${typologie}", array());

		if ($id_groupe = intval($config_typologie['id_groupe'])) {
			// Identification des champs acceptables pour un type.
			include_spip('base/objets');
			$description_table = lister_tables_objets_sql('spip_mots');
			$champs = $description_table['field'];

			include_spip('action/editer_objet');
			include_spip('inc/svptype_type_plugin');
			foreach ($types as $_type) {
				// On teste l'existence du type racine :
				// - si il n'existe pas on le rajoute,
				// - sinon on ne fait rien.
				// Dans tous les cas, on réserve l'id.
				if (!$id_type = type_plugin_lire($typologie, $_type['identifiant'], 'id_mot')) {
					// On insère le type racine (id_parent à 0).
					$set = array_intersect_key($_type, $champs);
					$set['id_parent'] = 0;
					$id_type = objet_inserer('mot', $id_groupe, $set);

					// Enregistrement du type ajouté.
					++$types_ajoutes;
				}

				// On traite maintenant les sous-types si :
				// -- le groupe est arborescent
				// -- il existe des sous-types dans le fichier pour le type racine
				// -- on est sur que le type racine existe
				if ($config_typologie['est_arborescente']
				and isset($_type['sous-types'])
				and $id_type) {
					// On insère les sous-types si ils ne sont pas déjà présentes dans la base.
					foreach ($_type['sous-types'] as $_sous_type) {
						if (!type_plugin_lire($typologie, $_sous_type['identifiant'], 'id_mot')) {
							// On insère le sous-type feuille sous son parent (un seul niveau permis).
							$set = array_intersect_key($_sous_type, $champs);
							$set['id_parent'] = $id_type;
							if (objet_inserer('mot', $id_groupe, $set)) {
								// Enregistrement du type ajouté.
								++$types_ajoutes;
							}
						}
					}
				}
			}
		}
	}

	return $types_ajoutes;
}


/**
 * Exporte de la base de données les types de plugin appartenant à une même typologie dans un fichier sur
 * le serveur.
 *
 * @api
 *
 * @param string $typologie
 *        Identifiant de la typologie concernée : categorie, tag...
 *
 * @return bool|string
 *         Le nom du fichier d'export ou false si erreur.
 */
function typologie_plugin_exporter($typologie) {

	// Initialisation de la sortie.
	$retour = false;

	// Déterminer les informations du groupe typologique.
	include_spip('inc/config');
	$configuration_typologie = lire_config("svptype/typologies/${typologie}", array());

	// Extraction des types de plugin pour la typologie concernée
	$types_exportes = array();
	if ($id_groupe = intval($configuration_typologie['id_groupe'])) {
		// Identification des champs exportables pour un type de plugin.
		$champs = array('identifiant', 'titre', 'descriptif');

		// Extraction de tous les types racine pour la typologie concernée.
		// -- si la typologie est arborescente, les feuilles sont de profondeur 1 et sont acquises par la suite.
		$where = array(
			'id_groupe=' . $id_groupe,
			'profondeur=0'
		);

		$types_racine = sql_allfetsel($champs, 'spip_mots', $where);
		if ($types_racine) {
			if ($configuration_typologie['est_arborescente']) {
				include_spip('inc/svptype_type_plugin');
				$where[1] = 'profondeur=1';
				foreach ($types_racine as $_cle => $_type) {
					// Recherche des types enfants qui sont forcément des feuilles.
					$where = 'id_parent=' . type_plugin_lire($typologie, $_type['identifiant'], 'id_mot');
					$types_feuille = sql_allfetsel($champs, 'spip_mots', $where);

					// Construction du tableau arborescent des types
					$types_exportes[$_cle] = $_type;
					if ($types_feuille) {
						$types_exportes[$_cle]['sous-types'] = $types_feuille;
					}
				}
			} else {
				$types_exportes = $types_racine;
			}
		}
	}

	// Ecriture des types de plugin dans un fichier d'export local au serveur
	// -- Formatage du contenu exportés en json
	$export = json_encode($types_exportes, JSON_PRETTY_PRINT);
	// -- Création du répertoire d'export et construction du nom du fichier
	$dir = sous_repertoire(_DIR_TMP, 'svptype');
	$date = date('YmdHi');
	$fichier = "${dir}${typologie}_${date}.json";
	// -- Ecriture du fichier
	if (ecrire_fichier($fichier, $export)) {
		$retour = $fichier;
	}

	return $retour;
}


/**
 * Importe une liste d'affectations (type de plugin, plugin) pour une typologie donnée.
 * Les affectations de la liste déjà présentes en base de données sont ignorées.
 *
 * @api
 *
 * @param string $typologie
 *        Identifiant de la typologie concernée : categorie, tag...
 * @param array  $affectations
 *        Tableau des affectations (type de plugin, plugin).
 *
 * @return int
 *         Nombre d'affectations ajoutées.
 */
function typologie_plugin_importer_affectation($typologie, $affectations) {

	// Initialisation du nombre d'affectations catégorie-plugin ajoutées.
	$nb_affectations_ajoutees = 0;

	if ($affectations) {
		// Déterminer les informations du groupe typologique.
		include_spip('inc/config');
		$configuration_typologie = lire_config("svptype/typologies/${typologie}", array());

		if ($id_groupe = intval($configuration_typologie['id_groupe'])) {
			// Initialisation d'un enregistrement d'affectation.
			$set = array(
				'id_groupe' => $id_groupe
			);

			include_spip('inc/svptype_type_plugin');
			foreach ($affectations as $_affectation) {
				// On contrôle tout d'abord que l'affectation est correcte :
				// -- type et préfixe sont renseignés,
				// -- le type existe dans la base.
				if (!empty($_affectation['type'])
				and !empty($_affectation['prefixe'])
				and ($id_mot = type_plugin_lire($typologie, $_affectation['type'], 'id_mot'))) {
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
						if (!$configuration_typologie['max_affectations']
						or (sql_countsel('spip_plugins_typologies', $where) < $configuration_typologie['max_affectations'])) {
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


/**
 * Exporte les affectations (type de plugin, plugin) appartenant à la même typologie dans un fichier sur
 * le serveur.
 *
 * @api
 *
 * @param string $typologie
 *        Identifiant de la typologie concernée : categorie, tag...
 *
 * @return bool|string
 *         Le nom du fichier d'export ou false si erreur.
 */
function typologie_plugin_exporter_affectation($typologie) {

	// Initialisation de la sortie.
	$retour = false;

	// Déterminer les informations du groupe typologique.
	include_spip('inc/config');
	$configuration_typologie = lire_config("svptype/typologies/${typologie}", array());

	$affectations_exportees = array();
	if ($id_groupe = intval($configuration_typologie['id_groupe'])) {
		// On récupère le préfixe et l'identifiant du type via une jointure avec spip_mots.
		$from = array('spip_plugins_typologies', 'spip_mots');
		$select = array(
			'spip_plugins_typologies.prefixe',
			'spip_mots.identifiant'
		);
		$where = array(
			'spip_plugins_typologies.id_groupe=' . $id_groupe,
			'spip_plugins_typologies.id_mot=spip_mots.id_mot'
		);

		$affectations_exportees = sql_allfetsel($select, $from, $where);
	}

	// Ecriture des types de plugin dans un fichier d'export local au serveur
	// -- Formatage du contenu exportés en json
	$export = json_encode($affectations_exportees, JSON_PRETTY_PRINT);
	// -- Création du répertoire d'export et construction du nom du fichier
	$dir = sous_repertoire(_DIR_TMP, 'svptype');
	$date = date('YmdHi');
	$fichier = "${dir}${typologie}_affectation_${date}.json";
	// -- Ecriture du fichier
	if (ecrire_fichier($fichier, $export)) {
		$retour = $fichier;
	}

	return $retour;
}


/**
 * Lister les fichiers d'export JSON stockés dans le répertoire temporaire idoine.
 *
 * @api
 *
 * @param string $typologie
 *        Identifiant de la typologie concernée : categorie, tag...
 *
 * @return array
 *         Tableau associatif des fichiers d'export fournissant, le chemin complet, le nom sans extension,
 *         la date et la taille de chaque fichier.
 */
function typologie_plugin_lister_export($typologie) {

	// Initialisation de la liste
	$exports = array();

	// Recherche des fichiers dans le répertoire idoine.
	$fichiers = glob(_DIR_TMP . "svptype/${typologie}_*.json*");

	// Construction de la liste pour l'affichage.
	foreach ($fichiers as $_fichier) {
		$exports[] = array(
			'fichier' => $_fichier,
			'nom'     => basename($_fichier, '.json'),
			'date'    => filemtime($_fichier),
			'taille'  => filesize($_fichier)
		);
	}

	return $exports;
}


/**
 * Elabore la collection des types de plugin pour la typologie concernée au format demandé par l'API REST SVP API.
 *
 * @param string $typologie
 *        Identifiant de la typologie concernée : categorie, tag...
 * @param array $filtres
 *      Tableau des critères de filtrage additionnels.
 *
 * @return array
 *      Tableau des types des plugins demandés.
 */
function typologie_plugin_collectionner($typologie, $filtres) {

	// Initialisation de la collection
	$types = array();

	// Récupération de la configuration de la typologie
	include_spip('inc/config');
	$configuration_typologie = lire_config("svptype/typologies/${typologie}");

	// Récupérer les informations sur la typologie et le groupe de mots correspondant.
	// -- on loge l'identifiant de la typologie.
	$types['typologie'] = array('identifiant' => $typologie);
	// -- on ajoute le titre du groupe de mots
	$id_groupe = $configuration_typologie['id_groupe'];
	$select = array('titre');
	$where = array('id_groupe=' . intval($id_groupe));
	$types['typologie'] = array_merge(
		$types['typologie'],
		sql_fetsel($select, 'spip_groupes_mots', $where)
	);

	// Récupérer la liste des catégories (filtrée ou pas).
	// -- Extraction des seuls champs significatifs et nécessaires aux traitements suivants :
	//    id_parent sera transformé en son identifiant et id_mot sera supprimé après avoir été utilisé.
	$informations = array(
		'titre',
		'descriptif',
		'id_parent',
		'profondeur',
		'identifiant',
		'id_mot'
	);
	include_spip('inc/svptype_type_plugin');
	$collection = type_plugin_repertorier($typologie, $filtres, $informations);

	// On refactore le tableau de sortie en un tableau associatif indexé par les identifiants de type de plugin.
	include_spip('inc/svptype_mot');
	if ($collection) {
		$index_collection = $configuration_typologie['collection']['nom'];
		$types[$index_collection] = array();
		foreach ($collection as $_type) {
			$type = $_type;

			// Identification du parent et suppression de l'id_parent qui devient inutile.
			$type['parent'] = $_type['id_parent']
				? mot_lire_identifiant($_type['id_parent'])
				: '';
			unset($type['id_parent']);

			// Déterminer la liste des plugins affectés pour les types feuille.
			if ($_type['profondeur'] == $configuration_typologie['max_profondeur']) {
				$affectations = type_plugin_repertorier_affectation(
					$typologie,
					array('id_mot' => $_type['id_mot'])
				);
				$type['plugins'] = array_column($affectations, 'prefixe');
			}
			unset($type['id_mot']);

			// Ajout au tableau de sortie avec l'identifiant en index
			$types[$index_collection][$_type['identifiant']] = $type;
		}
	}

	return $types;
}


/**
 * Construit la condition SQL issue de l'analyse du critère `{typologie_plugin[ identifiant1, identifiant2]}`.
 *
 * @param array  $typologies
 *        Liste des identifiants de typologie passé en argument du critère. Si la liste est vide on intègre
 *        toutes les typologies dans la condition.
 * @param string $table
 *        Identifiant de la table sur laquelle porte la condition, soit `mots` ou `groupes_mots`.
 *
 * @return string
 *         Condition SQL traduisant le critère (égalité ou IN).
 */
function typologie_plugin_elaborer_critere($typologies, $table) {

	// Initialisation de la condition pour le cas où il y aurait une erreur :
	// -- on annule l'effet du critère.
	$condition = '1=1';

	// Acquérir la configuration des typologies, en particulier pour les id des groupes.
	include_spip('inc/config');
	$configurations_typologie = lire_config('svptype/typologies', array());

	// Construire la liste des id des groupes correspondants à ou aux typologies incluses dans le critère.
	$ids_groupe = array();
	if (!$typologies) {
		// Le critère est de la forme {typologie_plugin} :
		// -- on récupère toutes les typologies supportées.
		$ids_groupe = array_column($configurations_typologie, 'id_groupe');
	} else {
		// Le critère est de la forme {typologie_plugin identifiant}, {typologie_plugin #ENV{variable}} ou
		// {typologie_plugin #GET{variable}} :
		// -- on parcourt tous les index du tableau des identifiants fourni pour trouver les id de groupe correspondants.
		foreach ($typologies as $_typologie) {
			if (isset($configurations_typologie[$_typologie])) {
				$ids_groupe[] = $configurations_typologie[$_typologie]['id_groupe'];
			}
		}
	}

	// Construire la condition en évitant un IN si le critère ne désigne qu'une typologie.
	if ($ids_groupe) {
		if (count($ids_groupe) == 1) {
			$condition = "${table}.id_groupe=" . $ids_groupe[0];
		} else {
			$condition = "'${table}.id_groupe' IN" . ' (' . implode(',', $ids_groupe) . ')';
		}
	}

	return $condition;
}
