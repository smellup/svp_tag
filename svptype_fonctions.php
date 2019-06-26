<?php

include_spip('inc/svptype_typologie');

function critere_typologie_plugin_dist($idb, &$boucles, $critere) {

	// Initialisation de la table (spip_mots ou spip_groupes_mots)
	$boucle = &$boucles[$idb];
	$table = $boucle->id_table;

	// Acquérir la configuration des typologies et en particulier les id des groupes.
	include_spip('inc/config');
	$typologies = lire_config('svptype/typologies', array());

	// On traite le critère suivant que la version ou la branche est explicitement fournie ou pas.
	if (!empty($critere->param)) {
		// La ou les versions/branches sont explicites dans l'appel du critere.
		// - on boucle sur les paramètres sachant qu'il est possible de fournir une liste séparée par une virgule
		//   (ex categorie, tag)
		$ids_groupe = array();
		foreach ($critere->param as $_param) {
			if (isset($_param[0]->texte)) {
				$typologie = $_param[0]->texte;
				if (isset($typologies[$typologie])) {
					$ids_groupe[] = $typologies[$typologie]['id_groupe'];
				}
			}
		}

		if (!$ids_groupe) {
			$ids_groupe = array_column($typologies, 'id_groupe');
		}

		$boucle->where[] = array(
			"'IN'",
			"'${table}.id_groupe'",
			"'(" . implode(',', $ids_groupe) . ")'"
		);
	} else {
		// Pas de version ou de branche explicite dans l'appel du critere.
		// - on regarde si elle est dans le contexte
		$boucle->hash .= '
		$typologie = isset($Pile[0][\'typologie\']) ? $Pile[0][\'typologie\'] : \'\';
		$where' . $i . '  = $creer_where($version, \'' . $table . '\', \'' . $op . '\');
		';
		$boucle->where[] = '$where' . $i;
		$i++;
	}
}
