<?php
/**
 * Ce fichier contient l'API de gestion des contrôles.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Charge ou recharge les descriptions des contrôles à partir des fichiers YAML.
 * La fonction optimise le chargement en effectuant uniquement les traitements nécessaires
 * en fonction des modifications, ajouts et suppressions des contrôles identifiés
 * en comparant les md5 des fichiers YAML.
 *
 * @api
 *
 * @param bool   $recharger
 *        Si `true` force le rechargement de tous les types de noisette, sinon le chargement se base sur le
 *        md5 des fichiers YAML. Par défaut vaut `false`.
 *
 * @return bool
 *        `false` si une erreur s'est produite, `true` sinon.
 */
function controle_charger($recharger) {

	// Retour de la fonction
	$retour = true;

	// On recherche les contrôles directement par leur fichier YAML de configuration car il est
	// obligatoire. La recherche s'effectue dans le path en utilisant le dossier relatif fourni.
	if ($fichiers = find_all_in_path('controles/', '.+[.]json$')) {
		// Initialisation des tableaux de types de noisette.
		$controles_a_ajouter = $controles_a_changer = $controles_a_effacer = array();

		// Récupération de la description complète des types de noisette déjà enregistrés de façon :
		// - à gérer l'activité des types en fin de chargement
		// - de comparer les signatures md5 des noisettes déjà enregistrées. Si on force le rechargement il est inutile
		//   de gérer les signatures et les noisettes modifiées ou obsolètes.
		$controles_existants = controle_lister();
		$signatures = array();
		if (!$recharger) {
			$signatures = array_column($controles_existants, 'signature', 'type_controle');
			// On initialise la liste des types de noisette à supprimer avec l'ensemble des types de noisette déjà stockés.
			$controles_a_effacer = $signatures ? array_keys($signatures) : array();
		}

		foreach ($fichiers as $_squelette => $_chemin) {
			$type_controle = basename($_squelette, '.json');
			// Si on a forcé le rechargement ou si aucun md5 n'est encore stocké pour le type de noisette
			// on positionne la valeur du md5 stocké à chaine vide.
			// De cette façon, on force la lecture du fichier YAML du type de noisette.
			$md5_stocke = (isset($signatures[$type_controle]) and !$recharger)
				? $signatures[$type_controle]
				: '';

			// Initialisation de la description par défaut du type de contrôle
			$description_defaut = array(
				'type_controle' => $type_controle,
				'nom'           => $type_controle,
				'description'   => '',
				'periode'       => '',
				'actif'         => 'oui',
				'date'          => date('Y-m-d h:s:i'),
				'signature'     => '',
			);

			// On vérifie que le md5 du fichier YAML est bien différent de celui stocké avant de charger
			// le contenu. Sinon, on passe au fichier suivant.
			$md5 = md5_file($_chemin);
			if ($md5 != $md5_stocke) {
				// Lecture du fichier YAML.
				lire_fichier($_chemin, $json);

				// Décodage du contenu JSON en structure de données PHP.
				$description = json_decode($json, true);

				$description['signature'] = $md5;
				// Complétude de la description avec les valeurs par défaut
				$description = array_merge($description_defaut, $description);

				if (!$md5_stocke or $recharger) {
					// Le type de noisette est soit nouveau soit on est en mode rechargement forcé:
					// => il faut le rajouter.
					$controles_a_ajouter[] = $description;
				} else {
					// La description stockée a été modifiée et le mode ne force pas le rechargement:
					// => il faut mettre à jour le type de noisette.
					$controles_a_changer[] = $description;
					// => et il faut donc le supprimer de la liste de types de noisette obsolètes
					$controles_a_effacer = array_diff($controles_a_effacer, array($type_controle));
				}
			} else {
				// Le type de noisette n'a pas changé et n'a donc pas été rechargé:
				// => Il faut donc juste indiquer qu'il n'est pas obsolète.
				$controles_a_effacer = array_diff($controles_a_effacer, array($type_controle));
			}
		}

		// Mise à jour des contrôles en base de données :
		// -- Suppression des contrôles obsolètes ou de tous les contrôles si on est en mode rechargement forcé.
		// -- Update des contrôles modifiés.
		// -- Insertion des nouveaux contrôles.

		// Mise à jour de la table des contrôles
		$from = 'spip_controles';
		// -- Suppression des pages obsolètes ou de toute les pages non virtuelles si on est en mode
		//    rechargement forcé.
		if (sql_preferer_transaction()) {
			sql_demarrer_transaction();
		}
		if ($controles_a_effacer) {
			sql_delete($from, sql_in('type_controle', $controles_a_effacer));
		} elseif ($forcer_chargement) {
			sql_delete($from);
		}
		// -- Update des contrôels modifiés
		if ($controles_a_changer) {
			sql_replace_multi($from, $controles_a_changer);
		}
		// -- Insertion des nouveaux contrôles
		if ($controles_a_ajouter) {
			sql_insertq_multi($from, $controles_a_ajouter);
		}
		if (sql_preferer_transaction()) {
			sql_terminer_transaction();
		}
	}

	return $retour;
}

/**
 * Renvoie l'information brute demandée pour l'ensemble des contrôles utilisés
 * ou toute les descriptions si aucune information n'est explicitement demandée.
 *
 * @param string $information
 *        Identifiant d'un champ de la description d'un contrôle.
 *        Si l'argument est vide, la fonction renvoie les descriptions complètes et si l'argument est
 *        un champ invalide la fonction renvoie un tableau vide.
 *
 * @return array
 *        Tableau de la forme `[type_controle]  information ou description complète`. Les champs textuels
 *        sont retournés en l'état, le timestamp `maj n'est pas fourni.
 */
function controle_lister($information = '') {

	// Initialiser le tableau de sortie en cas d'erreur
	$controles = array();

	$from = 'spip_controles';
	$trouver_table = charger_fonction('trouver_table', 'base');
	$table = $trouver_table($from);
	$champs = array_keys($table['field']);
	if ($information) {
		// Si une information précise est demandée on vérifie sa validité
		$information_valide = in_array($information, $champs);
		$select = array('type_controle', $information);
	} else {
		// Tous les champs sauf le timestamp 'maj' sont renvoyés.
		$select = array_diff($champs, array('maj'));
	}

	if ((!$information or ($information and $information_valide))
	and ($controles = sql_allfetsel($select, $from))) {
		if ($information) {
			$controles = array_column($controles, $information, 'type_controle');
		} else {
			$controles = array_column($controles, null, 'type_controle');
		}
	}

	return $controles;
}
