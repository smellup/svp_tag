<?php
/**
 * Ce fichier contient des compléments de l'API de gestion de l'objet plugin.
 *
 * @package SPIP\SVP\PLUGIN\API
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Affecte, pour un plugin donné, un nouveau type de plugin.
 *
 * @param int|string $plugin    La valeur du préfixe ou de l'id du plugin.
 * @param int        $id_mot    Id du mot matérialisant le type de plugin à désaffecter.
 * @param string     $typologie Typologie à laquelle appartient le type de plugin (categorie, tag...).
 *
 * @return bool `true` si l'insertion se passe correctement ou `false` sinon.
 */
function plugin_affecter_type_plugin($plugin, $id_mot, $typologie) {

	// La sortie est toujours ok a priori
	$retour = true;

	// On détermine le préfixe du plugin qui est utilisé dans la table des affectations :
	// -- si c'est le préfixe on le passe en majuscules pour être cohérent avec le stockage en base.
	// -- sinon on lit le préfixe à partir de l'id du plugin.
	if ($id_plugin = intval($plugin)) {
		include_spip('inc/svp_plugin');
		$prefixe = plugin_lire($id_plugin, 'prefixe');
	} else {
		$prefixe = strtoupper($plugin);
	}

	// On récupère l'id du groupe pour la typologie concernée.
	include_spip('inc/config');
	$id_groupe = lire_config("svptype/typologies/${typologie}/id_groupe", 0);

	// Contruire l'enregistrement d'une affectation définie par le préfixe du plugin et l'id du mot représentant
	// le type de plugin. Pour simplifier la récupération des affectations d'une typologie on ajoute aussi l'id du
	// groupe matérialisant la typologie.
	$set = array(
		'prefixe'   => $prefixe,
		'id_mot'    => intval($id_mot),
		'id_groupe' => $id_groupe
	);
	if (sql_insertq('spip_plugins_typologies', $set) === false) {
		$retour = false;
	} else {
		pipeline(
			'post_affectation_plugin',
			array(
				'args' => array(
					'typologie' => $typologie,
					'prefixe'   => $prefixe,
					'id_mot'    => $id_mot,
				),
				'data' => array()
			)
		);
	}

	return $retour;
}

/**
 * Supprime, pour un plugin donné, une affectation d'un type de plugin.
 *
 * @param int|string $plugin La valeur du préfixe ou de l'id du plugin.
 * @param int        $id_mot Id du mot matérialisant le type de plugin à désaffecter.
 *
 * @return bool `true` si la suppresion se passe correctement ou `false` sinon.
 */
function plugin_desaffecter_type_plugin($plugin, $id_mot) {

	// La sortie est toujours ok a priori
	$retour = true;

	// On détermine le préfixe du plugin qui est utilisé dans la table des affectations :
	// -- si c'est le préfixe on le passe en majuscules pour être cohérent avec le stockage en base.
	// -- sinon on lit le préfixe à partir de l'id du plugin.
	if ($id_plugin = intval($plugin)) {
		include_spip('inc/svp_plugin');
		$prefixe = plugin_lire($id_plugin, 'prefixe');
	} else {
		$prefixe = strtoupper($plugin);
	}

	// Cibler l'affectation dans la table par le préfixe du plugin et l'id du mot représentant le type de plugin.
	// Il est inutile de préciser l'id du groupe (donc la typologie) car un mot est uniquement rattaché à une seule
	// typologie.
	$where = array(
		'prefixe=' . sql_quote($prefixe),
		'id_mot=' . intval($id_mot)
	);
	if (sql_delete('spip_plugins_typologies', $where) === false) {
		$retour = false;
	}

	return $retour;
}

/**
 * Liste, pour un plugin donné, les types de plugin qui lui sont affectés pour une typologie donnée.
 *
 * @param int|string $plugin    La valeur du préfixe ou de l'id du plugin.
 * @param string     $typologie Typologie à laquelle appartient le type de plugin (categorie, tag...).
 *
 * @return array Liste des types de plugin d'une typologie (id du mot représentant le type)
 *               affectés au plugin concerné. Vide si aucune affectation.
 */
function plugin_lister_type_plugin($plugin, $typologie) {

	// On détermine le préfixe du plugin qui est utilisé dans la table des affectations :
	// -- si c'est le préfixe on le passe en majuscules pour être cohérent avec le stockage en base.
	// -- sinon on lit le préfixe à partir de l'id du plugin.
	if ($id_plugin = intval($plugin)) {
		include_spip('inc/svp_plugin');
		$prefixe = plugin_lire($id_plugin, 'prefixe');
	} else {
		$prefixe = strtoupper($plugin);
	}

	// On récupère l'id du groupe pour la typologie concernée.
	include_spip('inc/config');
	$id_groupe = lire_config("svptype/typologies/${typologie}/id_groupe", 0);

	// Cibler les affectations par le préfixe du plugin et l'id du groupe représentant la typologie.
	$where = array(
		'prefixe=' . sql_quote($prefixe),
		'id_groupe=' . $id_groupe
	);
	if ($affectations = sql_allfetsel('id_mot', 'spip_plugins_typologies', $where)) {
		// On renvoie la liste des id mot.
		$affectations = array_column($affectations, 'id_mot');
	}

	return $affectations;
}

function plugin_elaborer_condition($typologie, $id_mot = 0) {

	// Initialisation de la condition
	$condition = '0=1';

	// Récupération des plugins sous la forme [prefixe] = id_plugin
	$select = array('prefixe', 'spip_plugins.id_plugin as id_plugin');
	$from = array('spip_plugins', 'spip_depots_plugins');
	$group_by = array('spip_plugins.id_plugin');
	$where = array('spip_depots_plugins.id_depot>0', 'spip_depots_plugins.id_plugin=spip_plugins.id_plugin');
	$plugins = sql_allfetsel($select, $from, $where, $group_by);
	$plugins = array_column($plugins, 'id_plugin', 'prefixe');

	if ($plugins) {
		// Récupération des affectations de plugin filtrées ou pas sur un type de plugin donné et présentées
		// sous la forme [prefixe] = affectation
		$filtres = $id_mot ? array('id_mot' => $id_mot) : array();
		$affectations = type_plugin_repertorier_affectation($typologie, $filtres);
		$plugins_affectes = array_column($affectations, null, 'prefixe');
		if (!$id_mot) {
			// On veut les plugins non encore affectés : on essaye de minimiser la liste dans le IN.
			if (!$plugins_affectes) {
				// Plutôt que de faire un IN on met une condition toujours vrai car on veut tous les plugins.
				$condition = '1=1';
			} elseif (count($plugins_affectes) > count($plugins) / 2) {
				$plugins_filtres = array_diff_key($plugins, $plugins_affectes);
				$condition = 'plugins.id_plugin IN (' . implode(',', $plugins_filtres) . ')';
			} else {
				$plugins_filtres = array_intersect_key($plugins, $plugins_affectes);
				$condition = 'plugins.id_plugin NOT IN (' . implode(',', $plugins_filtres) . ')';
			}
		} else {
			if ($plugins_affectes) {
				// On veut les plugins affectés au type de plugin passé en argument
				$plugins_filtres = array_intersect_key($plugins, $plugins_affectes);
				$condition = 'plugins.id_plugin IN (' . implode(',', $plugins_filtres) . ')';
			}
		}
	}

	return $condition;
}
