<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Modifie les champs du formulaire d'édition (création ou modification) d'un mot.
 *
 * Sur les mots appartenant à un groupe plugin :
 * - ajouter la saisie de l'identifiant juste avant le titre
 * - remplacer la saisie du parent par une saisie du type parent basée sur la saisie type_plugin.
 *
 * @pipeline formulaire_fond
 *
 * @param array $flux
 * 		Données du pipeline
 *
 * @return array
 * 		Données du pipeline complétées
**/
function svptype_formulaire_fond($flux) {

	if (($env = $flux['args']['contexte'])
	and ($flux['args']['form'] == 'editer_mot')
	and isset($env['id_groupe'])
	and ($id_groupe = intval($env['id_groupe']))) {
		// Formulaire d'édition d'un mot :
		// -- on teste si c'est un mot plugin (catégorie ou tag)
		include_spip('inc/svptype_mot');
		if (groupe_est_typologie_plugin($id_groupe)) {
			// Insertion de l'identifiant avant le titre
			$saisie_identifiant = recuperer_fond('formulaires/inclure/inc-type_plugin_identifiant', $env);

			$cherche = "/(<(li|div)[^>]*class=(?:'|\")editer-groupe[^>]*>)\s*(<(li|div)[^>]*class=(?:'|\")editer editer_titre)/is";
			if (preg_match($cherche, $flux['data'], $m)) {
				$flux['data'] = preg_replace(
					$cherche,
					'$1' . "\n${saisie_identifiant}" . '$3',
					$flux['data'],
					1
				);
			}

			// Remplacement de la saisie du mot parent par celle du type parent.
			// -- on complète l'environnement avec la typologie et le groupe principalement utile pour la création
			include_spip('inc/config');
			$typologies = lire_config('svptype/typologies', array());
			foreach ($typologies as $_typologie => $_config) {
				if ($_config['id_groupe'] == $id_groupe) {
					$env['typologie'] = $_typologie;
					break;
				}
			}
			$saisie_parent = recuperer_fond('formulaires/inclure/inc-type_plugin_parent', $env);

			$cherche = "/(<(li|div)[^>]*class=(?:'|\")editer editer_id_parent.*?<\/\\2>)/is";
			if (preg_match($cherche, $flux['data'], $m)) {
				$flux['data'] = preg_replace(
					$cherche,
					$saisie_parent,
					$flux['data'],
					1
				);
			}
		}
	}

	return $flux;
}




/**
 * Vérifie la saisie du formulaire d'édition d'un mot plugin.
 *
 * Sur les mots appartenant à un groupe plugin :
 * - l'identifiant doit être non vide et pas déjà utilisé
 *
 * @pipeline formulaire_verifier
 *
 * @param array $flux
 * 		Données du pipeline
 *
 * @return array
 * 		Données du pipeline complétées
**/
function svptype_formulaire_verifier($flux) {

	if ($flux['args']['form'] == 'editer_mot') {
		// Formulaire d'édition d'un mot :
		// -- on récupère l'id du groupe.
		if ($id_groupe = intval(_request('id_groupe'))) {
			include_spip('inc/svptype_mot');
			// On teste si c'est un mot plugin (catégorie ou tag)
			if (groupe_est_typologie_plugin($id_groupe)) {
				$identifiant = _request('identifiant');
				if (!$identifiant) {
					$flux['data']['identifiant'] = _T('info_obligatoire');
				} else {
					$from = 'spip_mots';
					$where = array('id_groupe=' . $id_groupe);
					if ($id_mot = intval(_request('id_mot'))) {
						// il faut exclure de la liste le mot lui-même si il existe déjà.
						$where[] = 'id_mot!=' . $id_mot;
					}
					if (($identifiants = sql_allfetsel('identifiant', $from, $where))
					and (in_array($identifiant, array_map('reset', $identifiants)))) {
						$flux['data']['identifiant'] = _T('svptype:identifiant_erreur_duplication');
					}
				}
			}
		}
	}

	return $flux;
}


/**
 * Insère des modifications juste avant la création d'un mot plugin (catégorie ou tag)
 *
 * Lors de la création d'un mot plugin :
 * - Ajoute l'identifiant du mot.
 *
 * @pipeline pre_insertion
 *
 * @param array $flux
 *     Données du pipeline
 *
 * @return array
 *     Données du pipeline complétées
 **/
function svptype_pre_insertion($flux) {

	if ($flux['args']['table'] == 'spip_mots') {
		// Création d'un mot :
		// -- L'identifiant et l'id du groupe doivent être fournis
		if ($identifiant = _request('identifiant')
		and ($id_groupe = intval(_request('id_groupe')))) {
			include_spip('inc/svptype_mot');
			// On teste si c'est un mot plugin (catégorie ou tag)
			if (groupe_est_typologie_plugin($id_groupe)) {
				$flux['data']['identifiant'] = $identifiant;
			}
		}
	}

	return $flux;
}


/**
 * Insère des modifications lors de l'édition de mots
 *
 * Lors de l'édition d'un mot plugin (catégorie ou tag) :
 * - Ajoute la modification de l'identifiant
 *
 * @pipeline pre_edition
 *
 * @param array $flux
 *     Données du pipeline
 *
 * @return array
 *     Données du pipeline complétées
**/
function svptype_pre_edition($flux) {

	if ($flux['args']['table'] == 'spip_mots'
	and $flux['args']['action'] == 'modifier') {
		// Edition d'un mot :
		// -- L'identifiant et l'id du groupe doivent être fournis
		if ($identifiant = _request('identifiant')
		and ($id_groupe = intval(_request('id_groupe')))) {
			// On teste si c'est un mot plugin (catégorie ou tag)
			include_spip('inc/svptype_mot');
			if (groupe_est_typologie_plugin($id_groupe)) {
				$flux['data']['identifiant'] = $identifiant;
			}
		}
	}

	return $flux;
}


/**
 * Exclure les groupes de mots et le mots-clés relatifs à une typologie de plugin si le critère typologie_plugin
 * n'est pas explicitement utilisé.
 *
 * @param array $boucle
 *     Description de la boucle
 *
 * @return array
 *     Description complétée de la boucle
**/
function svptype_pre_boucle($boucle) {

	if (($boucle->type_requete == 'mots')
	or ($boucle->type_requete == 'groupes_mots')) {

		$id_table = $boucle->id_table;
		if (!isset($boucle->modificateur['typologie_plugin'])) {
			// Récupérer les id des groupes matérialisant les typologies.
			include_spip('inc/config');
			if ($typologies = lire_config('svptype/typologies', array())) {
				$ids_groupe = array_column($typologies, 'id_groupe');

				// Restreindre aux mots cles ou au groupes non typologiques.
				$boucle->where[] = array("'NOT IN'", "'$id_table.id_groupe'", "'(".implode(',',$ids_groupe).")'");
			}
		}
	}

	return $boucle;
}


/**
 * Ajouter le champs identifiant dans l'affichage d'un mot plugin.
 *
 * @pipeline afficher_contenu_objet
 *
 * @param array $flux Données du pipeline
 *
 * @return array      Données du pipeline
**/
function svptype_afficher_contenu_objet($flux) {

	if (isset($flux['args']['type'], $flux['args']['id_objet'])) {
		// Détermination de l'objet affiché
		$objet = $flux['args']['type'];
		$id_objet = $flux['args']['id_objet'];

		if (($objet == 'mot') and $id_objet) {
			// On est bien en présence d'un mot:
			// -- on teste si c'est un mot plugin (catégorie ou tag)
			include_spip('inc/svptype_mot');
			if (($id_groupe = mot_lire_groupe($id_objet))
			and groupe_est_typologie_plugin($id_groupe)) {
				// On affiche l'identifiant du mot
				$contexte = array('id_mot' => $id_objet);
				$html_identifiant = recuperer_fond('prive/squelettes/inclure/inc-type_plugin_identifiant', $contexte);
				$flux['data'] .= $html_identifiant;
			}
		}
	}

	return $flux;
}


function svptype_declarer_collections_svp($collections) {

	// Les index désignent les collections.
	// -- SVP Typologie rajoute les collections, catégories, tags et les affectations de types.
	$collections['categories'] = array(
		'module'    => 'svptype',
		'filtres'   => array(
			array(
				'critere' => 'profondeur'
			)
		)
	);

	$collections['tags'] = array(
		'module'    => 'svptype',
		'filtres'   => array()
	);

	$collections['affectations'] = array(
		'module'    => 'svptype',
		'filtres'   => array(
			array(
				'critere' => 'typologie'
			),
			array(
				'critere' => 'type'
			)
		)
	);

	// -- SVP Typologie rajoute le filtre de catégorie dans la collection plugins.
	$collections['plugins']['filtres'][] = array(
		'critere' => 'categorie',
		'module'  => 'svptype'
	);

	return $collections;
}


/**
 * Complète la collection après son calcul standard.
 *
 * Pour la collection `plugins` :
 * - Ajoute pour chaque élément les champs de typologie comme categorie et tag.
 *
 * @pipeline post_collection_svp
 *
 * @param array $flux
 *     Données du pipeline
 *
 * @return array
 *     Données du pipeline complétées
**/
function svptype_post_collection_svp($flux) {

	// Extraction des informations sur la collection.
	// La collection et la configuration existent toujours.
	$collection = $flux['args']['collection'];

	// Seule la collection plugins nécessite des compléments, à savoir, les typologies.
	if ($collection == 'plugins') {
		// On récupère les typologies supportées.
		include_spip('inc/config');
		$typologies = lire_config('svptype/typologies', array());

		// Pour chaque typologie, on rajoute le champ nécessaire à tous les plugins de la collection.
		include_spip('inc/svptype_plugin');
		foreach ($typologies as $_typologie => $_configuration) {
			foreach ($flux['data'] as $_prefixe => $_plugin) {
				$flux['data'][$_prefixe][$_typologie] = plugin_lire_type($_prefixe, $_typologie);
			}
		}
	}

	return $flux;
}


/**
 * Complète la collection après son calcul standard.
 *
 * Pour la collection `plugins` :
 * - Ajoute pour chaque élément les champs de typologie comme categorie et tag.
 *
 * @pipeline post_collection_svp
 *
 * @param array $flux
 *     Données du pipeline
 *
 * @return array
 *     Données du pipeline complétées
**/
function svptype_post_ressource_svp($flux) {

	// Extraction des informations sur la collection.
	// La collection et la configuration existent toujours.
	$collection = $flux['args']['collection'];

	// Seule la collection plugins nécessite des compléments, à savoir, les typologies.
	if ($collection == 'plugins') {
		// On récupère les typologies supportées.
		include_spip('inc/config');
		$typologies = array_keys(lire_config('svptype/typologies', array()));

		// Pour chaque typologie, on rajoute le champ nécessaire soit au plugin concerné, soit à tous les
		// plugins de la collection.
		include_spip('inc/svptype_plugin');
		foreach ($typologies as $_typologie) {
			// C'est une requête de type ressource, la ressource désigne le préfixe.
			$flux['data']['plugin'][$_typologie] = plugin_lire_type($flux['args']['ressource'], $_typologie);
		}
	}

	return $flux;
}
