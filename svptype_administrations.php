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
	include_spip('inc/svptype_typologie');
	$configuration = array(
		'typologies' => typologie_plugin_configurer()
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
	typologie_plugin_creer_groupe();
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
	$ids_groupe = array_column(lire_config('svptype/typologies', array()), 'id_groupe');
	if ($ids_groupe) {
		foreach ($ids_groupe as $_id_groupe) {
			$where = array('id_groupe=' . intval($_id_groupe));
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
