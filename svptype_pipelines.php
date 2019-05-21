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
			$where = array('id_groupe=' . $id_groupe);
			$identifiant_groupe = sql_getfetsel('identifiant', 'spip_groupes_mots', $where);
			if ($identifiant_groupe == 'plugin-categories') {
				$from ='spip_mots';
				$where = array('id_groupe=' . $id_groupe);
				if ($identifiants = sql_allfetsel('identifiant', $from, $where)) {
					$identifiant = _request('identifiant');
					if (!$identifiant) {
						$flux['data']['identifiant'] = _T('info_obligatoire');
					} elseif (in_array($identifiant, array_map('reset', $identifiants))) {
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
		if ($identifiant = _request('identifiant')) {
			include_spip('inc/svptype_categorie');
			if (groupe_est_plugin(_request('id_groupe'))) {
				$flux['data']['identifiant'] = $identifiant;
			}
		}
	}

	return $flux;
}
