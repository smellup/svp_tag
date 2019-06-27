<?php

include_spip('inc/svptype_typologie');

function critere_typologie_plugin_dist($idb, &$boucles, $critere) {

	// Initialisation de la table (spip_mots ou spip_groupes_mots) et de la boucle concernée.
	$boucle = &$boucles[$idb];
	$table = $boucle->id_table;

	// On calcule le code des critères.
	// -- Initialisation avec le chargement de la fonction de calcul du critère.
	$boucle->hash .= '
	// TYPOLOGIE PLUGIN
	$creer_where = \'typologie_plugin_construire_critere\';';

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
	$where = $creer_where(array(' . implode(',', $typologies) . '), \'' . $table . '\');';
	$boucle->where[] = '$where';
}

function typologie_plugin_construire_critere($typologies, $table) {

	// Initialisation de la condition pour le cas où la syntaxe serait en erreur :
	// -- on annule l'effet du critère.
	$condition = '1=1';

	// Acquérir la configuration des typologies, en particulier pour les id des groupes.
	include_spip('inc/config');
	$configuration = lire_config('svptype/typologies', array());

	// Construire la liste des id des groupes correspondants à ou aux typologies incluses dans le critère.
	$ids_groupe = array();
	if (!$typologies) {
		// Le critère est de la forme {typologie_plugin} ou sa négation :
		// -- on récupère toutes les typologies supportées.
		$ids_groupe = array_column($configuration, 'id_groupe');
	} else {
		// Le critère est de la forme {typologie_plugin xxx}, {typologie_plugin #ENV[xxx}} ou sa négation :
		// -- on parcourt tous les index du tableau des typologies pour trouver les id de groupe correspondants.
		foreach ($typologies as $_typologie) {
			if (isset($configuration[$_typologie])) {
				$ids_groupe[] = $configuration[$_typologie]['id_groupe'];
			}
		}
	}

	// Construction de la condition.
	if ($ids_groupe) {
		if (count($ids_groupe) == 1) {
			$condition = "${table}.id_groupe=" . $ids_groupe[0];
		} else {
			$condition = "'${table}.id_groupe' IN" . ' (' . implode(',', $ids_groupe) . ')';
		}
	}

	return $condition;
}
