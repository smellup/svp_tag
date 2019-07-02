<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2019                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Supprimer un fichier d'export.
 *
 */
function action_supprimer_dump_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$fichier = $securiser_action();

	include_spip('inc/autoriser');
	if (autoriser('webmestre')) {
		spip_unlink($fichier);
	}
}
