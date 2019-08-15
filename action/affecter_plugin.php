<?php
/**
 * Action pour affecter un type de plugin à un plugin.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Action pour affecter un type de plugin à un plugin.
 *
 * L'argument attendu est `id_mot_actuel:prefixe:id_mot:typologie`.
 *
 * @uses plugin_desaffecter()
 * @uses plugin_affecter()
 *
 * @return void
 */
function action_affecter_plugin_dist($arguments = null) {

	// Récupération des arguments de façon sécurisée.
	if (is_null($arguments)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arguments = $securiser_action();
	}

	$arguments = explode(':', $arguments);
	list($id_mot_affecte, $id_plugin, $prefixe, $id_mot, $typologie) = $arguments;

	// Verification des autorisations
	include_spip('inc/autoriser');
	if (!autoriser('affecter', 'type_plugin', $id_plugin)) {
		include_spip('inc/minipres');
		echo minipres();
		exit();
	}

	include_spip('inc/svptype_plugin');
	// Si on a passé un id non nul dans $id_mot_affecte c'est qu'on veut changer ce mot par un nouveau.
	// Il faut donc supprimer cette affectation au préalable.
	if ($id_mot_affecte) {
		plugin_desaffecter($prefixe, $id_mot_affecte);
	}

	// Maintenant on peut ajouter la nouvelle affectation
	plugin_affecter($prefixe, $id_mot, $typologie);
}
