<?php
/**
 * Ce fichier contient les fonctions de création, de mise à jour et de suppression
 * du schéma de données propres au plugin (tables et configuration).
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Installation du schéma de données propre au plugin et gestion des migrations suivant
 * les évolutions du schéma.
 *
 * Le schéma comprend des tables et des variables de configuration.
 *
 * @api
 *
 * @param string $nom_meta_base_version
 * 		Nom de la meta dans laquelle sera rangée la version du schéma
 * @param string $version_cible
 * 		Version du schéma de données en fin d'upgrade
 *
 * @return void
 */
function svptype_upgrade($nom_meta_base_version, $version_cible) {

	$maj = array();

	// Créer la configuration par défaut du plugin
	$configuration = array(
		'typologies' => typologie_configurer()
	);

	// Création des tables
	$maj['create'] = array(
		array('maj_tables', array('spip_groupes_mots', 'spip_mots', 'spip_plugins_typologies')),
		array('ecrire_config', 'svptype', $configuration)
	);

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);

	// Création des groupes de mots nécessaires à la typologie des plugins
	// -- la configuration a déjà été mise à jour en BDD.
	typologie_creer_groupe();
}


/**
 * Suppression de l'ensemble du schéma de données propre au plugin, c'est-à-dire
 * les tables et les variables de configuration.
 *
 * @api
 *
 * @param string $nom_meta_base_version
 * 		Nom de la meta dans laquelle sera rangée la version du schéma
 *
 * @return void
 */
function svptype_vider_tables($nom_meta_base_version) {

	// on supprime les groupes et les mots-clés créés
	include_spip('inc/config');
	$typologies = lire_config('svptype/typologies', array());
	if ($typologies) {
		foreach ($typologies as $_typologie => $_config) {
			$where = array('id_groupe=' . intval($_config['id_groupe']));
			// suppression des mots-clés du groupe
			sql_delete('spip_mots', $where);
			// suppression du groupe pour la typologie
			sql_delete('spip_groupes_mots', $where);
		}
	}

	// on supprime les champs additionnels des tables existantes
	sql_alter('TABLE spip_groupes_mots DROP COLUMN identifiant');
	sql_alter('TABLE spip_mots DROP COLUMN identifiant');

	// on efface les tables créées par le plugin
	sql_drop_table('spip_plugins_typologies');

	// Effacer la meta de configuration du plugin
	effacer_meta('svptype');

	// on efface la meta du schéma du plugin
	effacer_meta($nom_meta_base_version);
}


/**
 * Initialise la configuration des typologies du plugin.
 *
 * @return array
 * 		Le tableau de la configuration par défaut qui servira à initialiser l'index `typologies` de la meta `svptype`.
 */
function typologie_configurer() {

	// Deux typologies actuellement :
	// - categorie : les catégories de plugin
	// - tag : les tags de plugin
	$config = array(
		'categorie' => array(
			'est_arborescente' => true,
			'id_groupe'        => 0,
			'max_affectations' => 1,
			'max_profondeur'   => 1
		),
		'tag'       => array(
			'est_arborescente' => false,
			'id_groupe'        => 0,
			'max_affectations' => 0,
			'max_profondeur'   => 0
		),
	);

	return $config;
}


/**
 * Création des groupes de mots nécessaires à la typologie des plugins.
 * Si le groupe existe déjà on ne fait rien, sinon on le crée en stockant l'id dans la configuration.
 *
 * @return void
 */
function typologie_creer_groupe() {

	// Les groupes de typologie de plugin ont les caractéristiques communes suivantes :
	// - groupe technique
	// - sans tables liées
	// - et uniquement pour les administrateurs complets.

	// On acquiert la configuration déjà enregistrée pour le plugin.
	include_spip('inc/config');
	$config = lire_config('svptype', array());

	if (!empty($config['typologies'])) {
		include_spip('action/editer_objet');
		$config_modifiee = false;
		foreach ($config['typologies'] as $_typologie => $_config) {
			// On vérifie d'abord si le groupe existe déjà. Si oui, on ne fait rien.
			if (!$_config['id_groupe']) {
				$groupe = array(
					'titre'             => "typologie-${_typologie}-plugin",
					'technique'         => 'oui',
					'mots_arborescents' => $_config['est_arborescente'] ? 'oui' : 'non',
					'tables_liees'      => '',
					'minirezo'          => 'oui',
					'comite'            => 'non',
					'forum'             => 'non',
				);
				if ($id_groupe = objet_inserer('groupe_mots', null, $groupe)) {
					$config['typologies'][$_typologie]['id_groupe'] = $id_groupe;
					$config_modifiee = true;
				} else {
					spip_log(
						"Erreur lors de l'ajout du groupe pour la typologie ${_typologie}",
						'svptype' . _LOG_ERREUR
					);
				}
			}
		}

		// Ecriture de la configuration mise à jour
		if ($config_modifiee) {
			ecrire_config('svptype', $config);
		}
	}
}
