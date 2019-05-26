<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Pipeline ieconfig pour l'import/export des catégories et des affectations de plugin.
 *
 * @param array $flux
 *
 * @return array
 */
function svptype_ieconfig($flux) {

	// On détermine l'action demandée qui peut être : afficher le formulaire d'export ou d'import, construire le
	// tableau d'export ou exécuter l'importation.
	$action = $flux['args']['action'];

	if ($action == 'form_export') {
		// Construire le formulaire d'export :
		// -- on demande le minimum à savoir si l'utilisateur veut inclure dans son export les catégories,
		//    les affectations des plugins ou les deux.
		$saisies = array(
			array(
				'saisie' => 'fieldset',
				'options' => array(
					'nom'   => 'svptype_fieldset',
					'label' => '<:svptype:ieconfig_titre:>',
					'icone' => 'svptype_menu-16.png',
				),
				'saisies' => array(
					array(
						'saisie' => 'oui_non',
						'options' => array(
							'nom' => 'svptype_export',
							'label' => '<:svptype:ieconfig_export_label:>',
							'explication' => '<:svptype:ieconfig_export_explication:>',
							'defaut' => '',
						),
					),
				),
			),
		);
		$flux['data'] = array_merge($flux['data'], $saisies);

	} elseif (($action == 'export') and (_request('svptype_export') == 'on')) {
		// Générer le tableau d'export
		$flux['data']['taxonomie'] = svptype_ieconfig_exporter();

	} elseif (($action == 'form_import') and isset($flux['args']['config']['taxonomie'])) {
		// Construire le formulaire d'import :
		// On affiche la version de Taxonomie et le schéma de base de données avec lesquels le fichier d'import
		// à été créé.
		$import = $flux['args']['config']['taxonomie'];
		$texte_explication = _T(
			'taxonomie:import_resume',
			array('version' => $import['version'], 'schema' => $import['schema'])
		);

		// La configuration : une case suffit car on applique toujours un remplacement et la configuration est
		// toujours présente dans un export.
		$informer_plugin = chercher_filtre('info_plugin');
		$version = $informer_plugin('taxonomie', 'version', true);
		$schema = $informer_plugin('taxonomie', 'schema');
		$plugin = $informer_plugin('taxonomie', 'nom');
		if ($schema == $import['schema']) {
			$explication_config = _T(
				'taxonomie:import_configuration_explication',
				array('version' => $version, 'schema' => $schema));
		} else {
			$explication_config = _T(
				'taxonomie:import_configuration_avertissement',
				array('version' => $version, 'schema' => $schema));
		}

		$saisies = array(
			array(
				'saisie'  => 'fieldset',
				'options' => array(
					'nom'   => 'taxonomie_export',
					'label' => $plugin,
					'icone' => 'taxonomie-24.png',
				),
				'saisies' => array(
					array(
						'saisie'  => 'explication',
						'options' => array(
							'nom'   => 'taxonomie_export_explication',
							'texte' => $texte_explication,
						),
					),
					array(
						'saisie'  => 'case',
						'options' => array(
							'nom'         => 'taxonomie_import_config',
							'label'       => '<:taxonomie:import_configuration_label:>',
							'label_case'  => '<:taxonomie:import_configuration_labelcase:>',
							'explication' => $explication_config
						),
					),
				),
			),
		);

		// On détermine les règnes existant dans le site: si un règne n'est pas présent sur le site
		// aucun import n'est possible.
		include_spip('taxonomie_fonctions');
		$regnes = regne_repertorier();
		if ($regnes) {
			// Pour chaque règne présent dans le fichier on crée la même liste de saisies instanciées pour le règne.
			foreach ($import['contenu']['regnes'] as $_regne) {
				// Titre du règne qui sert de séparation dans le formulaire.
				if (($import['contenu'][$_regne]['taxons']['edites'])
				or ($import['contenu'][$_regne]['especes'])) {
					$explication = ucfirst(_T("taxonomie:regne_${_regne}"));
					$saisies[0]['saisies'][] = array(
						'saisie'  => 'explication',
						'options' => array(
							'nom'   => "${_regne}_import_regne",
							'texte' => $explication,
						),
					);
				}

				// Taxons importés et édités (du règne au genre).
				$data = array();
				if ($import['contenu'][$_regne]['taxons']['edites']) {
					$data['fusionner'] = _T('taxonomie:import_taxons_edites_fusionner');
					// Identifier si le site contient déjà des taxons édités et décider de l'avertissement
					// nécessaire et des options.
					$taxons_modifies = taxon_preserver($_regne);
					if (!empty($taxons_modifies['edites'])) {
						$explication = _T('taxonomie:import_taxons_edites_explication');
						$data['ajouter'] = _T('taxonomie:import_taxons_edites_ajouter');
					} else {
						$explication = _T('taxonomie:import_taxons_edites_avertissement');
					}
					$saisies[0]['saisies'][] = array(
						'saisie'  => 'radio',
						'options' => array(
							'nom'         => "${_regne}_import_edites",
							'label'       => '<:taxonomie:import_taxons_edites_label:>',
							'explication' => $explication,
							'datas'       => $data,
						),
					);
				}

				// Espèces et taxons créés manuellement (non importés).
				$data = array();
				if ($import['contenu'][$_regne]['especes']) {
					$data['fusionner'] = _T('taxonomie:import_especes_fusionner');
					// Identifier si le site contient déjà des espèces et décider de l'avertissement
					// nécessaire et des options.
					$where = array(
						'regne=' . sql_quote($_regne),
						'importe=' . sql_quote('non'),
						'espece=' . sql_quote('oui')
					);
					$nb_especes = sql_countsel('spip_taxons', $where);
					if ($nb_especes > 0) {
						$explication = _T('taxonomie:import_especes_explication');
						$data['ajouter'] = _T('taxonomie:import_especes_ajouter');
					} else {
						$explication = _T('taxonomie:import_especes_avertissement');
					}
					$saisies[0]['saisies'][] = array(
						'saisie'  => 'radio',
						'options' => array(
							'nom'         => "${_regne}_import_especes",
							'label'       => '<:taxonomie:import_especes_label:>',
							'explication' => $explication,
							'datas'       => $data,
						),
					);
				}
			}
		} else {
			$saisies[0]['saisies'][] = array(
				'saisie'  => 'explication',
				'options' => array(
					'nom'   => "taxonomie_import_regne",
					'texte' => '<:taxonomie:import_regne_avertissement:>',
				),
			);
		}

		$flux['data'] = array_merge($flux['data'], $saisies);
	}

	// Import de la configuration
	if (($action == 'import') and isset($flux['args']['config']['taxonomie'])) {
		// On récupère les demandes d'importation.
		$importation['configuration'] = _request('taxonomie_import_config');

		include_spip('taxonomie_fonctions');
		$importation['donnees'] = array();
		$regnes = regne_repertorier();
		foreach ($regnes as $_regne) {
			if ($valeur = _request("${_regne}_import_edites")) {
				$importation['donnees']['edites'][$_regne] = $valeur;
			}
			if ($valeur = _request("${_regne}_import_especes")) {
				$importation['donnees']['especes'][$_regne] = $valeur;
			}
		}

		// Si au moins l'une est requise on appelle la fonction d'import.
		if ($importation['configuration']
		or $importation['donnees']) {
			if (!taxonomie_ieconfig_importer($importation, $flux['args']['config']['taxonomie'])) {
				$flux['data'] .= _T('taxonomie:ieconfig_probleme_import_config').'<br />';
			}
		}
	}

	return $flux;
}


// --------------------------------------------------------------------
// ------------------------- API IMPORT/EXPORT ------------------------
// --------------------------------------------------------------------

/**
 * Retourne le tableau d'export du plugin SVP Typologie contenant toujours sa configuration, la liste des catégories et
 * la liste des affectations plugin-catégorie.
 *
 * @return array
 *         Tableau d'export pour le pipeline ieconfig_exporter.
 **/
function svptype_ieconfig_exporter() {

	$export = array();

	// Insérer une en-tête qui permet de connaitre la version du plugin SVP Typologie utilisé lors de l'export
	$informer_plugin = chercher_filtre('info_plugin');
	$export['version'] = $informer_plugin('svptype', 'version', true);
	$export['schema'] = $informer_plugin('svptype', 'schema');
	$export['contenu'] = array();

	// Exportation de la configuration du plugin rangée dans la meta taxonomie uniquement.
	// Etant donné que l'on utilise ce pipeline pour les données de production de Taxonomie, on exporte aussi
	// sa configuration via ce pipeline et non via le pipeline ieconfig_metas.
	include_spip('inc/config');
	$export['configuration'] = lire_config('svptype');
	$export['contenu']['configuration'] = $export['configuration'] ? 'on' : '';

	// Exportation :
	// -- des catégories et des tags.
	// -- des affectations des catégories et des tags aux plugin.
	include_spip('inc/svptype');
	$groupes = $export['configuration']['groupes'];
	foreach ($groupes as $_nom => $_groupe) {
		// On répertorie les types
		$export['liste'][$_nom] = type_plugin_repertorier($_nom);
		$export['contenu']['liste'][$_nom] = $export['liste'][$_nom] ? 'on' : '';

		// On répertorie les affectation pour le type concerné.
		if ($export['liste'][$_nom]) {
			$export['affectation'][$_nom] = type_plugin_lister_affectation($_nom);
			$export['contenu']['affectation'][$_nom] = $export['affectation'][$_nom] ? 'on' : '';
		} else {
			$export['affectation'][$_nom] = array();
			$export['contenu']['affectation'][$_nom] = '';
		}
	}

	return $export;
}

/**
 * Importe tout ou partie d'un fichier d'export ieconfig contenant les données du noiZetier.
 *
 * @param array $importation
 *        Tableau associatif des demandes d'importation issues du formulaire ieconfig. Les index et les valeurs
 *        possibles sont :
 *        - `configuration` : vaut `on` pour importer ou null sinon
 *        - `pages_explicites` : vaut `on` pour importer ou null sinon
 *        - `compositions_virtuelles` : vaut `remplacer`, `ajouter` ou `fusionner` pour importer ou null sinon.
 *        - `noisettes` : vaut `remplacer` ou `ajouter` pour importer ou null sinon.
 * @param array $contenu_import
 *        Tableau des données du noiZetier issues du fichier d'import.
 *
 * @return bool
 */
function taxonomie_ieconfig_importer($importation, $contenu_import) {

	// Initialisation de la sortie
	$retour = true;

	// La configuration
	if ($importation['configuration']) {
		// On remplace la configuration actuelle par celle du fichier d'import.
		include_spip('inc/config');
		ecrire_config('taxonomie', $contenu_import['configuration']);
	}

	// Les taxons du règne au genre édités.
	if (!empty($importation['donnees']['edites'])) {
		foreach ($importation['donnees']['edites'] as $_regne => $_action) {
			// Importation des taxons édités du fichier d'import selon l'action requise.
			taxonomie_importer_taxons($contenu_import[$_regne]['taxons']['edites'], $_action, true);
		}
	}

	// Les espèces et les éventuels ascendants entre genre et espèce.
	if (!empty($importation['donnees']['especes'])) {
		foreach ($importation['donnees']['especes'] as $_regne => $_action) {
			// On commence par les taxons entre genre et espèce pour être sur que l'institution fonctionne.
			if (!empty($contenu_import[$_regne]['taxons']['crees'])) {
				taxonomie_importer_taxons($contenu_import[$_regne]['taxons']['crees'], $_action);
			}

			// Maintenant que les taxons entre genre et espèce ont été rajoutés on boucle sur les espèces et descendants.
			taxonomie_importer_taxons($contenu_import[$_regne]['especes'], $_action);
		}
	}

	// On invalide le cache
	include_spip('inc/invalideur');
	suivre_invalideur('taxonomie-import-config');

	return $retour;
}


function taxonomie_importer_taxons($taxons, $action, $taxons_edites = false) {

	// On boucle sur les taxons édités du règne et on les traite en fonction de l'action choisie.
	include_spip('action/editer_objet');
	foreach ($taxons as $_taxon) {
		// Pour chaque taxon on vérifié si il existe en base et si il est déjà édité.
		// On récupère en outre l'id pour utiliser l'API objet.
		$select = array('id_taxon', 'edite');
		$where = array('tsn=' . intval($_taxon['tsn']));
		if ($taxon_base = sql_fetsel($select, 'spip_taxons', $where)) {
			if (($action == 'fusionner')
			or (($action == 'ajouter') and ($taxon_base['edite'] != 'oui'))) {
				// On modifie l'espèce avec l'API qui appellera elle-même les pipelines pre_edition
				// pour la mise à jour de l'indicateur edite à oui et post_edition pour la modification
				// du statut qui dans ce cas ne produira rien.
				objet_modifier('taxon', $taxon_base['id_taxon'], $_taxon);
			}
		} elseif (!$taxons_edites) {
			// On force le statut à prop pour une espèce.
			if ($_taxon['espece'] == 'oui') {
				$_taxon['statut'] = 'prop';
			}

			objet_inserer('taxon', null, $_taxon);
		}
	}
}
