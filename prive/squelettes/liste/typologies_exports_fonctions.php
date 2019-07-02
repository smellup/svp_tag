<?php
/**
 * Ce fichier contient l'API de gestion des différentes typologie de plugin.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Lister les fichiers d'export JSON stockés dans le répertoire idoine.
 *
 * @param string $typologie
 *
 * @return array
 */
function typologie_plugin_export_lister($typologie) {

	// Initialisation de la liste
	$exports = array();

	// Recherche des fichiers dans le répertoire idoine.
	$fichiers = glob(_DIR_TMP . "svptype/${typologie}_*.json*");

	// Construction de la liste pour l'affichage.
	foreach ($fichiers as $_fichier) {
		$exports[] = array(
			'fichier' => $_fichier,
			'nom'     => basename($_fichier, '.json'),
			'date'    => filemtime($_fichier),
			'taille'  => filesize($_fichier)
		);
	}

	return $exports;
}
