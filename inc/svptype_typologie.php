<?php
/**
 * Ce fichier contient l'API de gestion des typologies de plugin vues comme un objet.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}



/**
 * Initialise la configuration des typologies du plugin.
 *
 * @return array
 * 		Le tableau de la configuration par défaut qui servira à initialiser l'index `typologies` de la meta `svptype`.
 */
function typologie_plugin_configurer() {

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
function typologie_plugin_creer_groupe() {

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
		foreach ($config['typologies'] as $_typologie => $config_typologie) {
			// On vérifie d'abord si le groupe existe déjà. Si oui, on ne fait rien.
			if (!$config_typologie['id_groupe']) {
				$groupe = array(
					'titre'             => "typologie-${_typologie}-plugin",
					'technique'         => 'oui',
					'mots_arborescents' => $config_typologie['est_arborescente'] ? 'oui' : 'non',
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


function typologie_plugin_construire_condition($typologies, $table) {

	// Initialisation de la condition pour le cas où la syntaxe serait en erreur :
	// -- on annule l'effet du critère.
	$condition = '1=1';

	// Acquérir la configuration des typologies, en particulier pour les id des groupes.
	include_spip('inc/config');
	$configuration = lire_config('svptype/typologies', array());

	// Construire la liste des id des groupes correspondants à ou aux typologies incluses dans le critère.
	$ids_groupe = array();
	if (!$typologies) {
		// Le critère est de la forme {typologie_plugin} ou sa négation :
		// -- on récupère toutes les typologies supportées.
		$ids_groupe = array_column($configuration, 'id_groupe');
	} else {
		// Le critère est de la forme {typologie_plugin xxx}, {typologie_plugin #ENV[xxx}} ou sa négation :
		// -- on parcourt tous les index du tableau des typologies pour trouver les id de groupe correspondants.
		foreach ($typologies as $_typologie) {
			if (isset($configuration[$_typologie])) {
				$ids_groupe[] = $configuration[$_typologie]['id_groupe'];
			}
		}
	}

	// Construction de la condition.
	if ($ids_groupe) {
		if (count($ids_groupe) == 1) {
			$condition = "${table}.id_groupe=" . $ids_groupe[0];
		} else {
			$condition = "'${table}.id_groupe' IN" . ' (' . implode(',', $ids_groupe) . ')';
		}
	}

	return $condition;
}
