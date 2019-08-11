<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// B
	'boite_info_explication' => 'SVP Typologie permet de gérer les différentes typologies des plugins (catégories, tags...).
	Pour chaque typologie, une vue permet de consulter et de modifier la liste des types de plugin, une autre de consulter et de modifier les affectations aux plugins et enfin une dernière d\'importer ou d\'exporter les types de plugins et affectations.',

	// C
	'categorie_identifiant_label' => 'Catégorie',
//	'categorie_export_affectation_bouton_titre' => 'Exporter des affectations plugin-catégorie',
	'categorie_export_affectation_form_titre' => 'Exportation d\'une liste d\'affectations plugin-catégorie',
//	'categorie_export_liste_bouton_titre' => 'Exporter une liste de catégories',
	'categorie_export_liste_form_titre' => 'Exportation d\'une liste de catégories',
	'categorie_export_vue_liste_label' => 'la liste des catégories',
	'categorie_export_vue_affectation_label' => 'la liste des affectations plugin-catégorie',
	'categorie_import_vue_liste_label' => 'une liste de catégories',
	'categorie_import_vue_affectation_label' => 'une liste d\'affectations plugin-catégorie',
	'categorie_import_affectation_bouton_titre' => 'Importer des affectations plugin-catégorie',
	'categorie_import_affectation_form_titre' => 'Importation d\'une liste d\'affectations plugin-catégorie',
	'categorie_import_liste_bouton_titre' => 'Importer une liste de catégories',
	'categorie_import_liste_form_titre' => 'Importation d\'une liste de catégories',
	'categorie_affectation_erreur_vide' => 'Aucune affectation plugin-catégorie',
	'categorie_affectation_titre' => 'Affectations plugin-catégorie',
	'categorie_affectation_ajouter' => 'Ajouter une affectation',
	'categorie_affectation_filtre_avec' => 'Plugins avec catégorie',
	'categorie_affectation_filtre_sans' => 'Plugins sans catégorie',
	'categorie_liste_erreur_vide' => 'Aucune catégorie disponible',
	'categorie_liste_ajouter' => 'Ajouter une catégorie',
	'categorie_liste_titre' => 'Liste des catégories',
	'categorie_menu_titre' => 'Catégories',
	'categorie_modifier_title' => 'Modifier cette catégorie',
	'categorie_desaffecter_label' => 'Retirer la catégorie',
	'categorie_affecter_label' => 'Affecter une catégorie',
	'categorie_changer_label' => 'Changer la catégorie',
	'categorie_creer_title' => 'Créer une catégorie',
	'categorie_creer_titre_defaut' => 'Nouvelle catégorie',
	'categorie_page_titre' => 'Gestion des catégories de plugin',
	'categorie_parent_label' => 'Catégorie parente',
	'categorie_parent_aucun_label' => '-- à la racine, aucun parent --',
	'categorie_selection_option_tout' => 'Toutes les catégories',
	'categorie_vidage_liste_label' => 'Supprimer les catégories ?',
	'categorie_vidage_affectation_label' => 'Supprimer les affectations plugin-categorie ?',

	// I
	'identifiant_label' => 'Identifiant',
	'identifiant_erreur_duplication' => 'L\'identifiant est déjà utilisé',
	'export_form_titre' => 'Exportation',
	'export_page_titre' => 'Exportation d\'une typologie de plugin',
	'export_liste_titre' => 'Fichiers d\'export',
	'export_vue_explication' => 'Le fichier JSON résultant est conforme au schéma autorisé pour une importation.',
	'export_vue_label' => 'Que voulez-vous exporter ?',
	'export_message_ok' => 'Exportation réussie.',
	'export_message_nok' => 'Erreur lors de l\'exportation.',
	'export_message_suppression' => 'Êtes-vous sûr de vouloir supprimer cet export ?',
	'export_message_vide' => 'Aucune donné à exporter.',
	'import_vue_label' => 'Que voulez-vous importer ?',
	'import_form_titre' => 'Importation',
	'import_page_titre' => 'Importation d\'une typologie de plugin',
	'import_fichier_label' => 'Fichier à importer',
	'import_fichier_explication' => 'Choisissez un fichier JSON conforme au schéma autorisé. Seuls les données n\'existant pas encore dans la base seront ajoutées.',
	'import_message_ok' => 'Importation réussie : @nb@.',
	'import_message_nok' => 'Aucune donnée importée.',
	'import_message_nok_json' => 'Le fichier choisi n\'est pas au format JSON.',

	// P
	'plugin_compteur_label' => 'Affectations',
	'plugin_categorie_erreur_vide' => 'Aucune catégorie',
	'plugin_tag_erreur_vide' => 'Aucun tag',

	// S
	'svptype_menu_titre' => 'Catégories et tags des plugins',

	// T
	'tag_identifiant_label' => 'Tag',
	'tag_export_vue_liste_label' => 'la liste des tags',
	'tag_export_vue_affectation_label' => 'la liste des affectations plugin-tag',
	'tag_import_vue_liste_label' => 'une liste de tags',
	'tag_import_vue_affectation_label' => 'une liste d\'affectations plugin-tag',
	'tag_export_affectation_bouton_titre' => 'Exporter une liste d\'affectations plugin-tag',
	'tag_export_affectation_form_titre' => 'Exportation d\'une liste d\'affectations plugin-tag',
	'tag_export_liste_bouton_titre' => 'Exporter une liste de tags',
	'tag_export_liste_form_titre' => 'Exportation d\'une liste de tags',
	'tag_import_affectation_bouton_titre' => 'Importer une liste d\'affectations plugin-tag',
	'tag_import_affectation_form_titre' => 'Importation d\'une liste d\'affectations plugin-tag',
	'tag_import_liste_bouton_titre' => 'Importer une liste de tags',
	'tag_import_liste_form_titre' => 'Importation d\'une liste de tags',
	'tag_affectation_erreur_vide' => 'Aucune affectation plugin-tag',
	'tag_liste_erreur_vide' => 'Aucun tag disponible',
	'tag_affectation_titre' => 'Affectations plugin-tag',
	'tag_affectation_ajouter' => 'Ajouter une affectation',
	'tag_affectation_filtre_avec' => 'Plugins avec tags',
	'tag_affectation_filtre_sans' => 'Plugins sans tag',
	'tag_liste_titre' => 'Liste des tags',
	'tag_liste_ajouter' => 'Ajouter un tag',
	'tag_menu_titre' => 'Tags',
	'tag_desaffecter_label' => 'Retirer le tag',
	'tag_affecter_label' => 'Affecter un tag',
	'tag_creer_title' => 'Créer un tag',
	'tag_creer_titre_defaut' => 'Nouveau tag',
	'tag_modifier_title' => 'Modifier ce tag',
	'tag_page_titre' => 'Gestion des tags de plugin',
	'tag_selection_option_tout' => 'Tous les tags',
	'tag_vidage_liste_label' => 'Supprimer les tags ?',
	'tag_vidage_affectation_label' => 'Supprimer les affectations plugin-tag ?',
	'typologie_menu_liste_titre' => 'Gérer la liste',
	'typologie_menu_affectation_titre' => 'Gérer l\'affectation des plugins',
	'typologie_menu_maintenance_titre' => 'Importer / exporter / vider',

	// V
	'vidage_form_titre' => 'Vidage',
	'vidage_liste_explication' => 'La suppression n\'est possible que si il n\'existe aucune affectation.',
	'vidage_message_ok' => 'Vidage réussi.',
	'vidage_message_nok' => 'Choisissez les données à supprimer.',
);
