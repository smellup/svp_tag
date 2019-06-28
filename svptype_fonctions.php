<?php

include_spip('inc/svptype_type_plugin');

function critere_typologie_plugin_dist($idb, &$boucles, $critere) {

	// Initialisation de la table (spip_mots ou spip_groupes_mots) et de la boucle concernée.
	$boucle = &$boucles[$idb];
	$table = $boucle->id_table;

	// On calcule le code des critères.
	// -- Initialisation avec le chargement de la fonction de calcul du critère.
	$boucle->hash .= '
	// TYPOLOGIE PLUGIN
	include_spip(\'inc/svptype_typologie\');
	$conditionner = \'typologie_plugin_construire_condition\';';

	// On identifie les typologies explicitement fournies dans le critère.
	$typologies = array();
	if (!empty($critere->param)) {
		// La ou les versions/branches sont explicites dans l'appel du critere.
		// - on boucle sur les paramètres sachant qu'il est possible de fournir une liste séparée par une virgule
		//   (ex categorie, tag)
		foreach ($critere->param as $_param) {
			if (isset($_param[0])) {
				$typologies[] = calculer_liste(array($_param[0]), array(), $boucles, $boucle->id_parent);
			}
		}
	}

	// On construit la condition en la calculant à l'exécution.
	$boucle->hash .= '
	$where = $conditionner(array(' . implode(',', $typologies) . '), \'' . $table . '\');';
	$boucle->where[] = '$where';
}
