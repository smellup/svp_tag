<?php
/**
 * Ce fichier contient l'API de gestion des différentes typologie de plugin.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


function liste_type_plugin_filtrer($typologie, $type, $vue) {

	$filtre = '';

	if ($type) {
		// Déterminer les informations de configuration de la typologie.
		include_spip('inc/config');
		if ($config = lire_config("svptype/typologies/${typologie}", array())) {
			// On détermine l'id et la profondeur du type.
			include_spip('inc/svptype_mot');
			if ($id = mot_lire_id($type)) {
				// On détermine la profondeur du type qui est plus fiable que de tester l'existence d'un "/".
				$profondeur = mot_lire_profondeur($id);

				if (($config['mots_arborescents'] == 'non')
				or ((($config['mots_arborescents'] == 'oui')) and ($profondeur == 1))) {
					// Le type est une feuille, on filtre sur son id.
					$filtre = 'plugins_typologies.id_mot=' . $id;
				} else {
					// La typologie est arborescente et le type est une racine.
					// Suivant la vue (liste ou affectation il faut utiliser un critère différent :
					// - liste : on veut les sous-types du type racine
					// - affectation : on veut toutes les affectations liées à ce type ou celui de ses enfants.
					if ($vue == 'liste') {
						$filtre = 'id_mot=' . $id;
					} else {
						$filtre = 'id_parent=' . $id;
					}
				}
			}
		}
	}

	return $filtre;
}
