<?php
/**
 * Gestion du formulaire d'affectation d'un type de plugin à un plugin.
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Chargement du formulaire d'affectation d'un type de plugin à un plugin donné.
 *
 * @param int    $id_plugin
 * @param string $typologie
 * @param array  $options
 *
 * @return array
 */
function formulaires_editer_affectations_charger($id_plugin, $typologie, $options = array()) {

	// Les options peuvent être limitées au booléen d'éditabilité
	if (!isset($options['editable'])) {
		$options['editable'] = true;
	}

	// L'éditabilité :) est définie par un test permanent (par exemple "associermots") ET le 4ème argument
	include_spip('inc/autoriser');
	$editable = ($options['editable'] and autoriser('affecter', 'plugin', $id_plugin));

	// On récupère le préfixe du plugin qui sert à indexer les affectations de types de plugin.
	include_spip('inc/svp_plugin');
	$prefixe = plugin_lire($id_plugin, 'prefixe');

	// Acquérir la configuration de la typologie.
	include_spip('inc/config');
	$configuration_typologie = lire_config("svptype/typologies/${typologie}", array());

	// On détermine si la typologie n'accepte qu'un type de plugin par plugin ou plus.
	$typologie_unique = ($configuration_typologie['max_affectations'] == 1);

	// On détermine la liste des types de plugin de la catégorie concernée qui sont déjà affectés au plugin.
	// On en déduit l'indication d'atteinte ou pas du nombre maximal d'affectations.
	include_spip('inc/svptype_plugin');
	$affectations = plugin_lister_type_plugin($prefixe, $typologie);
	$typologie_complete = false;
	if ($configuration_typologie['max_affectations']
	and (count($affectations) == $configuration_typologie['max_affectations'])) {
		$typologie_complete = true;
	}

	// Envoi des valeurs au formulaire.
	$valeurs = array(
		'id_plugin'          => $id_plugin,
		'prefixe'            => $prefixe,
		'typologie'          => $typologie,
		'affectations'       => $affectations,
		'profondeur_max'     => $configuration_typologie['max_profondeur'],
		'typologie_complete' => $typologie_complete,
		'typologie_unique'   => $typologie_unique,
		'attribut_id'        => "plugin-${typologie}",
		'affecter_plugin'    => '',
		'desaffecter_plugin' => '',
		'visible'            => 0,
		'editable'           => $editable,
	);

	// Les options non definies dans $valeurs sont passees telles quelles au formulaire html
	$valeurs = array_merge($options, $valeurs);

	return $valeurs;
}

/**
 * Traiter le post des informations d'édition de liens.
 *
 * Les formulaires peuvent poster dans quatre variables
 * - ajouter_lien et supprimer_lien
 *
 * Les deux premières peuvent être de trois formes différentes :
 * ajouter_lien[]="objet1-id1-objet2-id2"
 * ajouter_lien[objet1-id1-objet2-id2]="nimportequoi"
 * ajouter_lien['clenonnumerique']="objet1-id1-objet2-id2"
 * Dans ce dernier cas, la valeur ne sera prise en compte
 * que si _request('clenonnumerique') est vrai (submit associé a l'input)
 *
 * @param int    $id_plugin
 * @param string $typologie
 * @param array  $options
 *
 * @return array
 */
function formulaires_editer_affectations_traiter($id_plugin, $typologie, $options = array()) {

	// Les options peuvent être limitées au booléen d'éditabilité
	if (!isset($options['editable'])) {
		$options['editable'] = true;
	}

	// Initialisation du retour
	$retour = array(
		'editable' => $options['editable']
	);

	include_spip('inc/autoriser');
	if ($options['editable']
	and autoriser('affecter', 'plugin', $id_plugin)) {
		$action_desaffecter_plugin = _request('desaffecter_plugin');
		$action_affecter_plugin = _request('affecter_plugin');

		if ($action_desaffecter_plugin) {
			$affectation_courante = key($action_desaffecter_plugin);
			$desaffecter = charger_fonction(
				'desaffecter_plugin',
				'action',
				true
			);
			$desaffecter($affectation_courante);
		}

		if ($action_affecter_plugin) {
			$nouvelle_affectation = reset($action_affecter_plugin);
			if ($nouvelle_affectation) {
				$affecter = charger_fonction(
					'affecter_plugin',
					'action',
					true
				);
				$ancien_type_plugin = intval(key($action_affecter_plugin));
				$affecter("${ancien_type_plugin}:${nouvelle_affectation}");
			}
		}
	}

	return $retour;
}
