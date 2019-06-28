<?php
/**
 * Ce fichier contient l'API de gestion des différentes typologie de plugin.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


function selection_type_plugin_peupler($typologie, $options = array()) {

	// Déterminer les informations du groupe typologique.
	include_spip('inc/config');
	$config_typologie = lire_config("svptype/typologies/${typologie}", array());

	// Vérification des options.
	if (!$config_typologie['est_arborescente']) {
		// Seule l'option du titre est acceptée pour cette typologie car les types ne sont pas arborescents.
		$options['niveau_affiche'] = '';
		$options['optgroup'] = '';
		$options['parent'] = '';
	} else {
		// Pour les typologies arborescentes, il faut sélectionner les deux niveaux pour que l'option optgroup
		// ait un sens et le niveau feuille pour l'option parent.
		if (!empty($options['niveau_affiche'])) {
			$options['optgroup'] = '';
		}
		if ($options['niveau_affiche'] != 'feuille') {
			$options['parent'] = '';
		}
	}

	// Calcul du where en fonction des options :
	// - 'niveau_affiche' : si vide, on affiche tout, sinon on affiche un niveau donné.
	$where = array('id_groupe=' . intval($config_typologie['id_groupe']));
	if (!empty($options['niveau_affiche'])) {
		$where[] = 'profondeur=' . ($options['niveau_affiche'] == 'racine' ? 0 : 1);
	}
	// - 'parent' : si non vide, on affiche que les enfants du type parent.
	if (!empty($options['parent'])) {
		include_spip('inc/svptype_type_plugin');
		$id_parent = type_plugin_lire($typologie, $options['parent'], 'id_mot');
		$where[] = 'id_parent=' . $id_parent;
	}

	// Calcul du select en fonction des options :
	// - 'option_titre' : si vide on affiche l'identifiant, sinon le titre du type.
	$select = array('identifiant', 'profondeur');
	if (!empty($options['titre_affiche'])) {
		$select[] = 'titre';
	}

	// On récupère l'identifiant et éventuellement le titre des catégories de plugin requises uniquement.
	$from = array('spip_mots');
	$order_by = array('identifiant');
	$types = sql_allfetsel($select, $from, $where, '', $order_by);

	// On formate le tableau en fonction des options.
	// -- L'option optgroup a déjà été vérifiée : si elle est encore active, on est en présence
	//    d'une arborescence de types à présenter avec optgroup.
	//    Sinon, on veut une liste aplatie classée alphabétiquement.
	$data = array();
	if (!empty($options['optgroup'])) {
		// On retraite le tableau en arborescence conformément à ce qui est attendu par la saisie selection avec
		// optgroup.
		$groupes = array();
		// On initialise les groupes et on réserve l'index afin de pouvoir les reconnaitre lors de la prochaine boucle.
		foreach ($types as $_type) {
			if ($_type['profondeur'] == 0) {
				$index = empty($options['titre_affiche']) ? $_type['identifiant'] : $_type['titre'];
				$data[$index] = array();
				$groupes[$_type['identifiant']] = $index;
			}
		}
		// On ajoute ensuite les enfants dans le groupe parent.
		// -- préfixe des enfants au format de mots arborescents
		foreach ($types as $_type) {
			if ($_type['profondeur'] == 1) {
				// Extraction de l'identifiant du groupe (groupe/xxxx)
				$identifiants = explode('/', $_type['identifiant']);
				$index = $groupes[$identifiants[0]];
				$data[$index][$_type['identifiant']] = empty($options['titre_affiche'])
					? $_type['identifiant']
					: $_type['titre'];
			}
		}
	} else {
		// Si on ne veut pas de optgroup, on liste les types dans l'ordre alphabétique en gérant l'icone qui indique
		// la profondeur quand cela est nécessaire (arborescence).
		// Seule l'option du titre est à considérer.
		if (!empty($options['niveau_affiche'])) {
			$data = !empty($options['titre_affiche'])
				? array_column($types, 'titre', 'identifiant')
				: array_column($types, 'identifiant', 'identifiant');
		} else {
			include_spip('motsar_fonctions');
			foreach ($types as $_type) {
				$prefixe = mostar_tabulation($_type['profondeur']);
				$data[$_type['identifiant']] = empty($options['titre_affiche'])
					? $prefixe . $_type['identifiant']
					: $prefixe . $_type['titre'];
			}
		}
	}

    return $data;
}
