<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Supprimer un fichier d'export JSON.
 *
 * @return void
 */
function action_supprimer_dump_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$fichier = $securiser_action();

	include_spip('inc/autoriser');
	if (autoriser('webmestre')) {
		spip_unlink($fichier);
	}
}
