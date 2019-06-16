<?php
/**
 * Ce fichier contient l'API de gestion des différentes typologie de plugin.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


function liste_type_plugin_filtrer($type) {

	$filtre = '';

	if ($type) {
		// On détermine l'id et la profondeur du type.
		include_spip('inc/svptype_mot');
		if ($id = mot_lire_id($type)) {
			// On détermine la profondeur du type qui est plus fiable que de tester l'existence d'un "/".
			$profondeur = mot_lire_profondeur($id);

			if ($profondeur == 1) {
				// Le type est un enfant, on filtre sur son id.
				$filtre = 'plugins_typologies.id_mot=' . $id;
			} else {
				// Le type est une racine, on filtre en utilisant son id comme un parent.
				$filtre = 'id_parent=' . $id;
			}
		}
	}

	return $filtre;
}
