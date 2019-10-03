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
 * @param string $nom_meta_base_version Nom de la meta dans laquelle sera rangée la version du schéma
 * @param string $version_cible         Version du schéma de données en fin d'upgrade
 *
 * @return void
 */
function svptype_upgrade($nom_meta_base_version, $version_cible) {

	// Initialisation du tableau des mises à jour
	$maj = array();

	// Initialiser la configuration par défaut du plugin
	include_spip('inc/svptype_typologie');
	$configuration = array(
		'typologies' => typologie_plugin_configurer()
	);

	// Création des tables et sauvegarde de la configuration.
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
 * @param string $nom_meta_base_version Nom de la meta dans laquelle sera rangée la version du schéma
 *
 * @return void
 */
function svptype_vider_tables($nom_meta_base_version) {

	// on exporte les données du plugin avant de tout supprimer
	include_spip('inc/svptype_typologie');
	include_spip('inc/config');
	$typologies = array_keys(lire_config('svptype/typologies', array()));
	foreach ($typologies as $_typologie) {
		// Export des types
		typologie_plugin_exporter($_typologie);

		// Export des affectations
		typologie_plugin_exporter_affectation($_typologie);
	}

	// on supprime les groupes et les mots-clés créés
	$typologies = lire_config('svptype/typologies', array());
	if ($typologies) {
		foreach ($typologies as $_typologie => $_description) {
			// suppression des mots-clés du groupe
			typologie_plugin_vider($_typologie);
			// suppression du groupe pour la typologie
			$where = array('id_groupe=' . intval($_description['id_groupe']));
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
