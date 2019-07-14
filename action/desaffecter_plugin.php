<?php
/**
 * Action pour désaffecter un type de plugin d'un plugin.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Action pour désaffecter un type de plugin d'un plugin.
 *
 * L'argument attendu est `prefixe:id_mot`.
 *
 * @uses plugin_desaffecter()
 *
 * @return void
 */
function action_desaffecter_plugin_dist($arguments = null) {

	// Récupération des arguments de façon sécurisée.
	if (is_null($arguments)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arguments = $securiser_action();
	}

	$arguments = explode(':', $arguments);
	list($prefixe, $id_mot) = $arguments;

	include_spip('inc/svptype_plugin');
	plugin_desaffecter($prefixe, $id_mot);
}
