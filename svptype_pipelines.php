<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Modifie les champs du formulaire d'édition d'un mot.
 *
 * Sur les mots appartenant à un groupe plugin :
 * - ajouter la saisie de l'identifiant
 *
 * @pipeline formulaire_fond
 * @param array $flux
 * 		Données du pipeline
 * @return array
 * 		Données du pipeline complétées
**/
function svptype_formulaire_fond($flux) {

	if (($env = $flux['args']['contexte'])
	and ($flux['args']['form'] == 'editer_mot')
	and isset($env['id_groupe'])) {
		// Formulaire d'édition d'un mot :
		// -- on teste si c'est un mot plugin (catégorie ou tag)
		include_spip('inc/svptype');
		if (groupe_est_plugin($env['id_groupe'])) {
			// Construction de la saisie et positionnement en fin du formulaire.
			$saisie_identifiant = recuperer_fond('formulaires/inclure/identifiant_mot', $env);

			if (strpos($flux['data'], '<!--extra-->') !== false) {
				$flux['data'] = preg_replace(
					'%(<!--extra-->)%is',
					"${saisie_identifiant}\n" . '$1',
					$flux['data']
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
 * @param array $flux
 * 		Données du pipeline
 * @return array
 * 		Données du pipeline complétées
**/
function svptype_formulaire_verifier($flux) {

	if ($flux['args']['form'] == 'editer_mot') {
		// Formulaire d'édition d'un mot :
		// -- on récupère l'id du groupe.
		if ($id_groupe = intval(_request('id_groupe'))) {
			include_spip('inc/svptype');
			// On teste si c'est un mot plugin (catégorie ou tag)
			if (groupe_est_plugin($id_groupe)) {
				$identifiant = _request('identifiant');
				if (!$identifiant) {
					$flux['data']['identifiant'] = _T('info_obligatoire');
				} else {
					$from ='spip_mots';
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
 * @param array $flux
 *     Données du pipeline
 * @return array
 *     Données du pipeline complétées
 **/
function svptype_pre_insertion($flux) {

	if ($flux['args']['table'] == 'spip_mots') {
		// Création d'un mot :
		// -- L'identifiant et l'id du groupe doivent être fournis
		if ($identifiant = _request('identifiant')
		and ($id_groupe = intval(_request('id_groupe')))) {
			include_spip('inc/svptype');
			// On teste si c'est un mot plugin (catégorie ou tag)
			if (groupe_est_plugin($id_groupe)) {
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
 * @param array $flux
 *     Données du pipeline
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
			include_spip('inc/svptype');
			if (groupe_est_plugin($id_groupe)) {
				$flux['data']['identifiant'] = $identifiant;
			}
		}
	}

	return $flux;
}


/**
 * Ajouter le champs identifiant dans l'affichage d'un mot plugin.
 *
 * @pipeline afficher_contenu_objet
 * @param array $flux Données du pipeline
 * @return array      Données du pipeline
**/
function svptype_afficher_contenu_objet($flux){

	if (isset($flux['args']['type'], $flux['args']['id_objet'])) {
		// Détermination de l'objet affiché
		$objet = $flux['args']['type'];
		$id_objet = $flux['args']['id_objet'];

		if (($objet == 'mot') and $id_objet) {
			// On est bien en présence d'un mot:
			// -- on teste si c'est un mot plugin (catégorie ou tag)
			include_spip('inc/svptype');
			if (($id_groupe = mot_lire_groupe($id_objet))
			and groupe_est_plugin($id_groupe)) {
				// On affiche l'identifiant du mot
				$contexte = array('id_mot' => $id_objet);
				$html_identifiant = recuperer_fond('prive/inclure/mot_identifiant', $contexte);
				$flux['data'] .= $html_identifiant;
			}
		}
	}

	return $flux;
}
