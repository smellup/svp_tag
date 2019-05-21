<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function svptype_formulaire_verifier($flux) {

	// Personnalisation du formulaire de choix de l'article d'accueil
	if ($flux['args']['form'] == 'editer_mot') {
		// Vérifier que le nom de l'identifiant du mot n'est pas déjà utilisé dans son groupe.
		// -- On récupère le groupe et son identifiant pour vérifier qu'il est bien du type plugin-xxxx.
		if ($id_groupe = intval(_request('id_groupe'))) {
			include_spip('inc/svptype');
			if (groupe_est_plugin($id_groupe)) {
				$identifiant = _request('identifiant');
				if (!$identifiant) {
					$flux['data']['identifiant'] = _T('info_obligatoire');
				} else {
					$from ='spip_mots';
					$where = array('id_groupe=' . $id_groupe);
					if ($id_mot = intval(_request('id_mot'))) {
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
 * Insère des modifications juste avant la création d'un mot
 *
 * Lors de la création d'un mot :
 * - Ajoute l'identifiant du mot pour les groupes plugin.
 *
 * @pipeline pre_insertion
 * @param array $flux
 *     Données du pipeline
 * @return array
 *     Données du pipeline complétées
 **/
function svptype_pre_insertion($flux) {

	// lors de la création d'un mot
	if ($flux['args']['table'] == 'spip_mots') {
		if ($identifiant = _request('identifiant')
		and ($id_groupe = intval(_request('id_groupe')))) {
			include_spip('inc/svptype');
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
 * Lors de l'édition d'un mot :
 * - Modifie l'id_parent choisi et définit l'id_mot_racine et la profondeur
 * - Lors du déplacement dans un autre groupe, recalculer les héritages.
 *
 * Lors de l'édition d'un groupe de mot :
 * - Prend en compte l'option mots_arborescents
 *
 * @pipeline pre_edition
 * @param array $flux
 *     Données du pipeline
 * @return array
 *     Données du pipeline complétées
**/
function svptype_pre_edition($flux) {
	// lors de l'édition d'un mot
	if ($flux['args']['table'] == 'spip_mots'
	and $flux['args']['action'] == 'modifier') {
		if ($identifiant = _request('identifiant')
		and ($id_groupe = intval(_request('id_groupe')))) {
			include_spip('inc/svptype');
			if (groupe_est_plugin($id_groupe)) {
				$flux['data']['identifiant'] = $identifiant;
			}
		}
	}

	return $flux;
}
