<?php

function svptype_declarer_tables_auxiliaires($tables_auxiliaires) {

	// Tables des types de plugins (catégories et tags) : spip_typologies
	$typologies = array(
		'objet' 	 => "varchar(16) DEFAULT 'plugin_categorie' NOT NULL",
		'type'		 => "varchar(255) DEFAULT '' NOT NULL",
		'titre'      => "text DEFAULT '' NOT NULL",
		'descriptif' => "text DEFAULT '' NOT NULL",
		'parent'     => "varchar(255) DEFAULT '' NOT NULL",
		'profondeur' => "smallint(5) DEFAULT '0' NOT NULL",
	);

	$typologies_key = array(
		'PRIMARY KEY'    => 'objet, type',
		'KEY objet'      => 'objet',
		'KEY profondeur' => 'profondeur',
	);

	$tables_auxiliaires['spip_typologies'] = array(
		'field' => &$typologies,
		'key'   => &$typologies_key
	);

	// Tables de liens entre plugins et les types de plugins : spip_plugins_typologies
	$plugins_typologies = array(
		'objet' 	 => "varchar(16) DEFAULT 'plugin_categorie' NOT NULL",
		'type'		 => "varchar(255) DEFAULT '' NOT NULL",
		'prefixe' 	 => "varchar(16) DEFAULT 'plugin_categorie' NOT NULL",
	);

	$plugins_typologies_key = array(
		'PRIMARY KEY'    => 'objet, type, prefixe',
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
	$interface['table_des_tables']['typologies'] = 'typologies';

	// Les jointures
	// -- Entre spip_plugins_stats et spip_plugins
	$interface['tables_jointures']['spip_plugins'][] = 'plugins_typologies';

	return $interface;
}
