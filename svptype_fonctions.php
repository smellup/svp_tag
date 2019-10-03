<?php
/**
 * Ce fichier contient les filtres nécessaires aux squelettes de SVP Typologie.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// La plupart des fonction d'API des types de plugin sont utilisés dans les squelettes.
include_spip('inc/svptype_type_plugin');

/**
 * Critère de restriction d'une boucle mots ou groupes de mots à une ou plusieurs typologies de plugin.
 * Ce critère ne supporte pas la négation.
 *
 * Fonctionne sur les tables spip_mots et spip_groupes_mots.
 *
 * @package SPIP\SVPTYPE\TYPOLOGIE_PLUGIN\CRITERE
 *
 * @uses typologie_plugin_calculer_critere()
 *
 * @critere
 *
 * @example
 *   {typologie_plugin}, pour intégrer toutes les typologies supportées dans la boucle
 *   {typologie_plugin categorie}
 *   {typologie_plugin categorie,tag}
 *   {typologie_plugin #ENV{typologie}}, #ENV{typologie} désigne forcément une unique typologie
 *   {typologie_plugin #GET{typologie}}, #GET{typologie} désigne forcément une unique typologie
 *
 * @param string  $idb     Identifiant de la boucle.
 * @param array   $boucles AST du squelette.
 * @param Critere $critere Paramètres du critère dans la boucle.
 *
 * @return void
 */
function critere_typologie_plugin_dist($idb, &$boucles, $critere) {

	// Initialisation de la table (spip_mots ou spip_groupes_mots) et de la boucle concernée.
	$boucle = &$boucles[$idb];
	$table = $boucle->id_table;

	// On calcule le code des critères.
	// -- Initialisation avec le chargement de la fonction de calcul du critère.
	$boucle->hash .= '
	// TYPOLOGIE PLUGIN
	include_spip(\'inc/svptype_typologie\');
	$conditionner = \'typologie_plugin_calculer_critere\';';

	// On identifie les typologies explicitement fournies dans le critère.
	$typologies = array();
	if (!empty($critere->param)) {
		// La ou les typologies sont explicites dans l'appel du critere.
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
