<?php

include_spip('inc/svptype');

$json = '{
  "activite": [
    "activite/association",
    "activite/education",
    "activite/commerce",
    "activite/gestion",
    "activite/projet"
  ],
  "administration": [
    "administration/espace-prive",
    "administration/maintenance",
    "administration/performance",
    "administration/referencement",
    "administration/securite",
    "administration/statistique"
  ]
}';
$liste = json_decode($json, true);
//categorie_plugin_importer($liste);
