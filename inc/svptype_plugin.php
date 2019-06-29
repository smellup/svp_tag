<?php
/**
 * Ce fichier contient l'ensemble des constantes et fonctions de construction du contenu des réponses aux
 * requête à l'API SVP.
 *
 * @package SPIP\SVPTYPE\PLUGIN\API
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Retourne la description complète d'un objet plugin identifié par son préfixe.
 *
 * @api
 *
 * @param $prefixe
 *        La valeur du préfixe du plugin.
 * @param string $typologie
 *        Typologie que l'on souhaite renvoyer pour le plugin concernée : categorie, tag...
 *
 * @return string|array
 *         Le ou les types du plugin pour la typologie concernée.
 */
function plugin_lire_type($prefixe, $typologie) {

	// Initialisation du tableau de sortie
	static $types = array();

	// On passe le préfixe en majuscules pour être cohérent avec le stockage en base.
	$prefixe = strtoupper($prefixe);

	if (!isset($types[$typologie][$prefixe])) {
		// Récupération de la configuration de la typologie pour :
		// -- l'id du groupe
		// -- le nombre maximal de types affectables à un plugin.
		$configuration_typologie = lire_config("svptype/typologies/${typologie}", array());

		// Initialisation des affectations pour le plugin.
		$types[$typologie][$prefixe] = $configuration_typologie['max_affectations'] == 1 ? '' : array();

		// Lecture des affectations dans la table spip_plugins_typologies.
		$from = array('spip_plugins_typologies', 'spip_mots');
		$select = array('spip_mots.identifiant');
		$where = array(
			'spip_plugins_typologies.prefixe=' . sql_quote($prefixe),
			'spip_plugins_typologies.id_groupe=' . intval($configuration_typologie['id_groupe']),
			'spip_plugins_typologies.id_mot=spip_mots.id_mot'
		);

		if ($configuration_typologie['max_affectations'] == 1) {
			if ($type = sql_fetsel($select, $from, $where)) {
				$types[$typologie][$prefixe] = $type['identifiant'];
			}
		} else {
			if ($type = sql_allfetsel($select, $from, $where)) {
				$types[$typologie][$prefixe] = array_column($type, 'identifiant');
			}
		}
	}

	return $types[$typologie][$prefixe];
}
