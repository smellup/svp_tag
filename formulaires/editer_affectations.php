<?php
/**
 * Gestion du formulaire d'affectation d'un type de plugin à un plugin.
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Chargement du formulaire d'édition de liens.
 *
 * #FORMULAIRE_EDITER_LIENS{auteurs,article,23}
 *   pour associer des auteurs à l'article 23, sur la table pivot spip_auteurs_liens
 * #FORMULAIRE_EDITER_LIENS{article,23,auteurs}
 *   pour associer des auteurs à l'article 23, sur la table pivot spip_articles_liens
 * #FORMULAIRE_EDITER_LIENS{articles,auteur,12}
 *   pour associer des articles à l'auteur 12, sur la table pivot spip_articles_liens
 * #FORMULAIRE_EDITER_LIENS{auteur,12,articles}
 *   pour associer des articles à l'auteur 12, sur la table pivot spip_auteurs_liens
 *
 * @param int    $id_plugin
 * @param string $typologie
 * @param array  $options
 *
 * @return array
 */
function formulaires_editer_affectations_charger($id_plugin, $typologie, $options = array()) {

	// Les options peuvent être limitées au booléen d'éditabilité
	if (!isset($options['editable'])) {
		$options['editable'] = true;
	}

	// L'éditabilité :) est définie par un test permanent (par exemple "associermots") ET le 4ème argument
	include_spip('inc/autoriser');
	$editable = ($options['editable'] and autoriser('affecter', 'plugin', $id_plugin));

	// On récupère le préfixe du plugin qui sert à indexer les affectations de types de plugin.
	include_spip('inc/svp_plugin');
	$prefixe = plugin_lire($id_plugin, 'prefixe');

	// Acquérir la configuration de la typologie.
	include_spip('inc/config');
	$configuration_typologie = lire_config("svptype/typologies/${typologie}", array());

	// On détermine si la typologie n'accepte qu'un type de plugin par plugin ou plus.
	$typologie_unique = ($configuration_typologie['max_affectations'] == 1);

	// On détermine la liste des types de plugin de la catégorie concernée qui sont déjà affectés au plugin.
	// On en déduit l'indication d'atteinte ou pas du nombre maximal d'affectations.
	include_spip('inc/svptype_plugin');
	$affectations = plugin_lister_type_plugin($prefixe, $typologie);
	$typologie_complete = false;
	if ($configuration_typologie['max_affectations']
	and (count($affectations) == $configuration_typologie['max_affectations'])) {
		$typologie_complete = true;
	}

	// Envoi des valeurs au formulaire.
	$valeurs = array(
		'id_plugin'          => $id_plugin,
		'prefixe'            => $prefixe,
		'typologie'          => $typologie,
		'affectations'       => $affectations,
		'profondeur_max'     => $configuration_typologie['max_profondeur'],
		'typologie_complete' => $typologie_complete,
		'typologie_unique'   => $typologie_unique,
		'attribut_id'        => "plugin-${typologie}",
		'affecter_plugin'    => '',
		'desaffecter_plugin' => '',
		'visible'            => 0,
		'editable'           => $editable,
	);

	// les options non definies dans $valeurs sont passees telles quelles au formulaire html
	$valeurs = array_merge($options, $valeurs);

	return $valeurs;
}

/**
 * Traiter le post des informations d'édition de liens.
 *
 * Les formulaires peuvent poster dans quatre variables
 * - ajouter_lien et supprimer_lien
 * - remplacer_lien
 * - qualifier_lien
 * - ordonner_lien
 * - desordonner_liens
 *
 * Les deux premières peuvent être de trois formes différentes :
 * ajouter_lien[]="objet1-id1-objet2-id2"
 * ajouter_lien[objet1-id1-objet2-id2]="nimportequoi"
 * ajouter_lien['clenonnumerique']="objet1-id1-objet2-id2"
 * Dans ce dernier cas, la valeur ne sera prise en compte
 * que si _request('clenonnumerique') est vrai (submit associé a l'input)
 *
 * remplacer_lien doit être de la forme
 * remplacer_lien[objet1-id1-objet2-id2]="objet3-id3-objet2-id2"
 * ou objet1-id1 est celui qu'on enleve et objet3-id3 celui qu'on ajoute
 *
 * qualifier_lien doit être de la forme, et sert en complément de ajouter_lien
 * qualifier_lien[objet1-id1-objet2-id2][role] = array("role1", "autre_role")
 * qualifier_lien[objet1-id1-objet2-id2][valeur] = array("truc", "chose")
 * produira 2 liens chacun avec array("role"=>"role1","valeur"=>"truc") et array("role"=>"autre_role","valeur"=>"chose")
 *
 * ordonner_lien doit être de la forme, et sert pour trier les liens
 * ordonner_lien[objet1-id1-objet2-id2] = nouveau_rang
 *
 * desordonner_liens n'a pas de forme précise, il doit simplement être non nul/non vide
 *
 * @param string     $a
 * @param int|string $b
 * @param int|string $c
 * @param array|bool $options
 *                              - Si array, tableau d'options
 *                              - Si bool : valeur de l'option 'editable' uniquement
 * @param mixed      $id_plugin
 * @param mixed      $typologie
 * @param mixed      $id_groupe
 * @param mixed      $prefixe
 *
 * @return array
 */
function formulaires_editer_affectations_traiter($id_plugin, $typologie, $options = array()) {
	if (!isset($options['editable'])) {
		$options['editable'] = true;
	}
	$editable = $options['editable'];

	$res = array('editable' => $editable ? true : false);

	include_spip('inc/autoriser');
	if (autoriser('affecter', 'plugin', $id_plugin)) {
		$desaffecter_plugin = _request('desaffecter_plugin');
		$ajouter = _request('ajouter_lien');

		if ($desaffecter_plugin) {
			$desaffecter = charger_fonction(
				'desaffecter_plugin',
				'action',
				true
			);
			$desaffecter(key($desaffecter_plugin));
		}

		if ($ajouter) {
			if ($ajouter_objets = charger_fonction("editer_liens_ajouter_{$table_source}_{$objet}_{$objet_lien}", 'action', true)
			) {
				$ajout_ok = $ajouter_objets($ajouter);
			} else {
				$ajout_ok = false;
				include_spip('action/editer_liens');
				foreach ($ajouter as $k => $v) {
					if ($lien = lien_verifier_action($k, $v)) {
						$ajout_ok = true;
						list($objet1, $ids, $objet2, $idl) = explode('-', $lien);
						$qualifs = lien_retrouver_qualif($objet_lien, $lien);
						if ($objet_lien == $objet1) {
							lien_ajouter_liaisons($objet1, $ids, $objet2, $idl, $qualifs);
						} else {
							lien_ajouter_liaisons($objet2, $idl, $objet1, $ids, $qualifs);
						}
						set_request('id_lien_ajoute', $ids);
					}
				}
			}
		}
	}

	return $res;
}
