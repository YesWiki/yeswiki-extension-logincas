<?php
/**
 * French translation
 *
 * @category YesWiki
 * @package  Logincas
 * @author   Florian Schmitt <mrflos@lilo.org>
 * @license  https://www.gnu.org/licenses/agpl-3.0.en.html AGPL 3.0
 * @link     https://yeswiki.net
 */

$GLOBALS['translations'] = array_merge(
    $GLOBALS['translations'],
    array(
        'CAS_USERNAME' => 'Utilisateur CAS',
        'CAS_MESSAGE_INFO' => 'L\'identification est réservée aux personnes inscrites sur le serveur CAS.',
        'CAS_CONFIG_MISSING' => 'L\'extension logincas n\'est pas configurée. Il manque dans wakka.config.php :',
        'CAS_FALLBACK_NOTICE' => 'La connexion par CAS est désactivée, le formulaire habituel reste utilisable.',
        'CAS_READ_DOC' => 'Lire la documentation technique pour voir toutes les options de configuration',
        'CAS_SERVER_UNREACHABLE' => 'Le serveur CAS ne répond pas ou est mal configuré.',
        'CAS_AUTHENTICATION_FAILED' => 'L\'authentification sur le serveur CAS a échoué.',
        'CAS_COOKIES_REQUIRED' => 'Vous devez accepter les cookies pour pouvoir vous connecter.',

    )
);
