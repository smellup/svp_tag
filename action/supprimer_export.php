<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Supprimer un fichier d'export JSON.
 *
 * @return void
 */
function action_supprimer_export_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$fichier = $securiser_action();

	// Verification des autorisations
	include_spip('inc/autoriser');
	if (!autoriser('typologie')) {
		include_spip('inc/minipres');
		echo minipres();
		exit();
	}

	spip_unlink($fichier);
}
