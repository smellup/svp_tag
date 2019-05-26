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
function svptype_upgrade($nom_meta_base_version, $version_cible){

	$maj = array();

	// Créer la configuration par défaut du plugin
	$config_defaut = configurer_svptype();

	// Création des tables
	$maj['create'] = array(
		array('maj_tables', array('spip_groupes_mots', 'spip_mots', 'spip_plugins_typologies')),
		array('ecrire_config', 'svptype', $config_defaut)
	);

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);

	// Création des groupes de mots nécessaires à la typologie des plugins
	// -- la configuration a déjà été mise à jour en BDD.
	creer_groupes_svptype();
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
//	sql_delete('spip_groupes_mots', array('identifiant=' . sql_quote('plugin-categories')));

	// on supprime les champs additionnels des tables existantes
	sql_alter("TABLE spip_groupes_mots DROP COLUMN identifiant");
	sql_alter("TABLE spip_mots DROP COLUMN identifiant");

	// on efface les tables
	sql_drop_table('spip_plugins_typologies');

	// Effacer la meta de configuration du plugin
	effacer_meta('svptype');

	// on efface la meta du schéma du plugin
	effacer_meta($nom_meta_base_version);
}


/**
 * Initialise la configuration du plugin.
 *
 * @return array
 * 		Le tableau de la configuration par défaut qui servira à initialiser la meta `svptype`.
 */
function configurer_svptype() {

	$config = array(
		'groupes' => array(
			'categorie' => array(
				'identifiant'       => 'plugin-categories',
				'mots_arborescents' => 'oui',
				'id_groupe'         => 0
			),
			'tag'       => array(
				'identifiant'       => 'plugin-tags',
				'mots_arborescents' => 'non',
				'id_groupe'         => 0
			),
		),
	);

	return $config;
}


/**
 * Création des groupes de mots nécessaires à la typologie des plugins.
 * Si le groupe existe déjà on ne fait rien, sinon on le crée en stockant l'id dans la configuration.
 *
 * @param array $config
 * 		Configuration du plugin : données des groupes de mots.
 *
 * @return void
 */
function creer_groupes_svptype() {

	// Les groupes plugin ont les caractéristiques communes suivantes :
	// - groupe technique
	// - sans tables liées
	// - et uniquement pour les administrateurs complets.
	// En outre :
	// - plugin-categories : le groupe arborescent des catégories de plugin
	// - plugin-tags : le groupe non arborescent des tags de plugin (initialisé mais pas utilisé pour l'instant)
	include_spip('action/editer_objet');
	include_spip('inc/config');

	// On acquiert la configuration du plugin et donc celle des groupes.
	$config = lire_config('svptype', array());

	if (!empty($config['groupes'])) {
		$config_modifiee = false;
		foreach ($config['groupes'] as $_type => $_groupe) {
			// On vérifie d'abord si le groupe existe déjà. Si oui, on ne fait rien.
			if (!sql_countsel('spip_groupes_mots', array('identifiant=' . sql_quote($_groupe['identifiant'])))) {
				$groupe = array(
					'identifiant'       => $_groupe['identifiant'],
					'titre'             => $_groupe['identifiant'],
					'technique'         => 'oui',
					'mots_arborescents' => $_groupe['mots_arborescents'],
					'tables_liees'      => '',
					'minirezo'          => 'oui',
					'comite'            => 'non',
					'forum'             => 'non',
				);
				if ($id_groupe = objet_inserer('groupe_mots', null, $groupe)) {
					$config['groupes'][$_type]['id_groupe'] = $id_groupe;
					$config_modifiee = true;
				} else {
					spip_log("Erreur lors de l'ajout du groupe {$_groupe['identifiant']}", 'svptype' . _LOG_ERREUR);
				}
			}
		}

		// Ecriture de la configuration mise à jour
		if ($config_modifiee) {
			ecrire_config('svptype', $config);
		}
	}
}
