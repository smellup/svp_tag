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
 * - remplacer la saisie du groupe par un hidden avec l'id du groupe qui ne peut pas être modifié.
 *
 * @pipeline formulaire_fond
 *
 * @param array $flux Données du pipeline
 *
 * @return array Données du pipeline complétées
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

			// Remplacement de la sélection du groupe de mots par un hidden avec l'id du groupe.
			$hidden_id_groupe = "<input type=\"hidden\" name=\"id_groupe\" value=\"${id_groupe}\">";

			$cherche = "/(<(li|div)[^>]*class=(?:'|\")editer editer_groupe_mot.*?<\/\\2>)/is";
			if (preg_match($cherche, $flux['data'], $m)) {
				$flux['data'] = preg_replace(
					$cherche,
					$hidden_id_groupe,
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
 * @param array $flux Données du pipeline
 *
 * @return array Données du pipeline complétées
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
 * Insère des modifications juste avant la création d'un mot plugin (catégorie ou tag).
 *
 * Lors de la création d'un mot plugin :
 * - Ajoute l'identifiant du mot.
 *
 * @pipeline pre_insertion
 *
 * @param array $flux Données du pipeline
 *
 * @return array Données du pipeline complétées
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
 * Insère des modifications lors de l'édition de mots.
 *
 * Lors de l'édition d'un mot plugin (catégorie ou tag) :
 * - Ajoute la modification de l'identifiant
 *
 * @pipeline pre_edition
 *
 * @param array $flux Données du pipeline
 *
 * @return array Données du pipeline complétées
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
 * Exclure les groupes de mots et les mots-clés relatifs à une typologie de plugin si le critère typologie_plugin
 * n'est pas explicitement utilisé.
 *
 * @param object $boucle Description de la boucle.
 *
 * @return object Description complétée de la boucle.
**/
function svptype_pre_boucle($boucle) {

	// Vérifier qu'on n'a pas un critère utilisant l'id de la table auquel cas on ne fait.
	if ((($boucle->type_requete == 'mots')
		and (!isset($boucle->modificateur['criteres']['id_mot']))
		and (!isset($boucle->modificateur['criteres']['id_groupe'])))
	or (($boucle->type_requete == 'groupes_mots')
		and (!isset($boucle->modificateur['criteres']['id_groupe'])))) {
		// Vérification de l'existence ou pas du critère {typologie_plugin}
		$typologie_plugin = false;
		foreach ($boucle->criteres as $_critere) {
			if (isset($_critere->op)
			and ($_critere->op == 'typologie_plugin')) {
				$typologie_plugin = true;
				break;
			}
		}

		// Si le critère n'est pas explicite dans la boucle, alors on exclut tous les types de plugins
		// et les groupes typologiques du résultat que l'on soit dans l'espace privé ou public.
		if (!$typologie_plugin) {
			// Récupérer les id des groupes matérialisant les typologies.
			include_spip('inc/config');
			if ($typologies = lire_config('svptype/typologies', array())) {
				$ids_groupe = array_column($typologies, 'id_groupe');

				// Restreindre aux mots-cles ou au groupes non typologiques.
				$table = $boucle->id_table;
				$boucle->where[] = array(
					"'NOT'",
					array(
						"'IN'",
						"'${table}.id_groupe'",
						"'(" . implode(',', $ids_groupe) . ")'"
					)
				);
			}
		}
	}

	return $boucle;
}

/**
 * Ajoute le champs identifiant dans l'affichage d'un mot plugin.
 *
 * @pipeline afficher_contenu_objet
 *
 * @param array $flux Données du pipeline
 *
 * @return array Données du pipeline complétées
**/
function svptype_afficher_contenu_objet($flux) {

	// On est bien en présence d'un objet
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

/**
 * Utilisation du pipeline affiche milieu.
 *
 * - Ajoute les formulaires d'édition des types de plugin pour chaque typologie supportée.
 *
 * @pipeline affiche_milieu
 *
 * @param array $flux Données du pipeline
 *
 * @return array Données du pipeline mise à jour.
 */
function svptype_affiche_milieu($flux) {

	// Si on est sur la page d'un plugin, il faut inserer les formulaires d'affectations des types de plugin.
	if ($exec = trouver_objet_exec($flux['args']['exec'])
		and ($exec['edition'] !== true) // page visu
		and ($type = $exec['type'])
		and ($type == 'plugin')
		and ($id_table_objet = $exec['id_table_objet'])
		and isset($flux['args'][$id_table_objet])
		and ($id_plugin = intval($flux['args'][$id_table_objet]))
	) {
		// On charge les typologies supportées.
		include_spip('inc/config');
		$configurations_typologie = lire_config('svptype/typologies', array());

		// On construit le formulaire à insérer pour chaque typologie.
		$texte = '';
		foreach ($configurations_typologie as $_typologie => $_configuration) {
			$texte .= recuperer_fond(
				'prive/objets/editer/affectations',
				array(
					'id_plugin' => $id_plugin,
					'typologie' => $_typologie,
					'options'   => array(
						'id_groupe' => $_configuration['id_groupe'],
						'editable'  => true
					)
				)
			);
		}

		// On insère le formulaire à l'endroit convenu (avant le texte du plugin).
		if ($p = strpos($flux['data'], '<!--affiche_milieu-->')) {
			$flux['data'] = substr_replace($flux['data'], $texte, $p, 0);
		}
	}

	return $flux;
}

/**
 * Déclare de nouvelles collections (les typologies, les affectations) et met à jour les collections
 * existantes déjà déclarées par SVP API (plugins).
 *
 * @pipeline declarer_collections_svp
 *
 * @param array $collections Configuration des collections déjà déclarées.
 *
 * @return array Collections complétées.
 */
function svptype_declarer_collections_svp($collections) {

	// Les index désignent les collections. SVP Typologie rajoute :
	// -- les collections correspondant aux typologies supportées
	include_spip('inc/config');
	$configurations_collection = array_column(lire_config('svptype/typologies', array()), 'collection');
	foreach ($configurations_collection as $_collection) {
		// Le nom d'une collection est l'index du tableau de déclaration.
		$collections[$_collection['nom']] = $_collection;
		// Inutile donc de garder le nom dans le tableau.
		unset($collections[$_collection['nom']]['nom']);
	}

	// -- la collection des affectations.
	$collections['affectations'] = array(
		'module'    => 'svptype',
		'filtres'   => array(
			array(
				'critere'         => 'typologie',
				'est_obligatoire' => true
			),
			array(
				'critere'         => 'type',
				'est_obligatoire' => false
			),
			array(
				'critere'         => 'prefixe',
				'est_obligatoire' => false
			)
		)
	);

	// -- SVP Typologie rajoute le filtre de catégorie dans la collection plugins proposée par défaut par SVP API.
	$collections['plugins']['filtres'][] = array(
		'critere'         => 'categorie',
		'module'          => 'svptype',
		'est_obligatoire' => false
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
 * @param array $flux Données du pipeline
 *
 * @return array Données du pipeline complétées
**/
function svptype_post_collection_svp($flux) {

	// Extraction des informations sur la collection.
	// La collection et la configuration existent toujours.
	$collection = $flux['args']['collection'];

	// Seule la collection plugins nécessite des compléments, à savoir, les typologies.
	if ($collection == 'plugins') {
		// On récupère les typologies supportées.
		include_spip('inc/config');
		$configurations_typologie = lire_config('svptype/typologies', array());

		// Pour chaque typologie, on rajoute le champ nécessaire à tous les plugins de la collection.
		include_spip('inc/svptype_type_plugin');
		foreach ($configurations_typologie as $_typologie => $_configuration) {
			foreach ($flux['data'] as $_prefixe => $_plugin) {
				$affectations = type_plugin_repertorier_affectation(
					$_typologie,
					array('prefixe' => $_prefixe)
				);
				if ($_configuration['max_affectations'] == 1) {
					$affectations = array_shift($affectations);
					$flux['data'][$_prefixe][$_typologie] = $affectations['identifiant_mot'];
				} else {
					$flux['data'][$_prefixe][$_typologie] = array_column($affectations, 'identifiant_mot');
				}
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
 * @param array $flux Données du pipeline
 *
 * @return array Données du pipeline complétées
**/
function svptype_post_ressource_svp($flux) {

	// Extraction des informations sur la collection.
	// La collection et la configuration existent toujours.
	$collection = $flux['args']['collection'];

	// Seule la collection plugins nécessite des compléments, à savoir, les typologies.
	if ($collection == 'plugins') {
		// On récupère les typologies supportées.
		include_spip('inc/config');
		$configurations_typologie = lire_config('svptype/typologies', array());

		// Pour chaque typologie, on rajoute le champ nécessaire soit au plugin concerné, soit à tous les
		// plugins de la collection.
		include_spip('inc/svptype_type_plugin');
		foreach ($configurations_typologie as $_typologie => $_configuration) {
			// C'est une requête de type ressource, la ressource désigne le préfixe.
			$affectations = type_plugin_repertorier_affectation(
				$_typologie,
				array('prefixe' => $flux['args']['ressource'])
			);
			if ($_configuration['max_affectations'] == 1) {
				$affectations = array_shift($affectations);
				$flux['data']['plugin'][$_typologie] = $affectations['identifiant_mot'];
			} else {
				$flux['data']['plugin'][$_typologie] = array_column($affectations, 'identifiant_mot');
			}
		}
	}

	return $flux;
}
