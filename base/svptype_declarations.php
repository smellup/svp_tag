<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Ajouter le champ `identifiant` à la table des mots.
 * Ce champ est une chaine sans espace qui représente un id textuel unique (l'unicité se définit
 * au sein d'un groupe de mots).
 *
 * @pipeline declarer_tables_objets_sql
 *
 * @param array $tables
 *     Description des tables
 *
 * @return array
 *     Description complétée des tables
 */
function svptype_declarer_tables_objets_sql($tables){

	// Colonne 'identifiant'
	$tables['spip_mots']['field']['identifiant'] = "varchar(255) DEFAULT '' NOT NULL";

	return $tables;
}


function svptype_declarer_tables_auxiliaires($tables_auxiliaires) {

	// Tables de liens entre plugins et les types de plugins : spip_plugins_typologies
	$plugins_typologies = array(
		'id_groupe' => 'bigint(21) DEFAULT 0 NOT NULL',
		'id_mot'    => 'bigint(21) DEFAULT 0 NOT NULL',
		'prefixe'   => "varchar(30) DEFAULT '' NOT NULL"
	);

	$plugins_typologies_key = array(
		'PRIMARY KEY'   => 'id_mot, prefixe',
		'KEY id_groupe' => 'id_groupe'
	);

	$tables_auxiliaires['spip_plugins_typologies'] = array(
		'field' => &$plugins_typologies,
		'key'   => &$plugins_typologies_key
	);

	return $tables_auxiliaires;
}


function svptype_declarer_tables_interfaces($interface) {
	// Les tables
	$interface['table_des_tables']['plugins_typologies'] = 'plugins_typologies';

	// Les jointures
	// -- Entre spip_plugins_typologies et spip_plugins
	$interface['tables_jointures']['spip_plugins']['prefixe'] = 'plugins_typologies';
	$interface['tables_jointures']['spip_mots']['id_mot'] = 'plugins_typologies';

	return $interface;
}
