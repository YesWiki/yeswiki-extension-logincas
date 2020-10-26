<?php
/**
 * Library of CAS and wiki/bazar users functions
 * 
 * @category YesWiki
 * @package  Login-cas
 * @author   Florian Schmitt <mrflos@lilo.org>
 * @license  https://www.gnu.org/licenses/agpl-3.0.en.html AGPL 3.0
 * @link     https://yeswiki.net
 */

/**
 * Attempt login to CAS and retrieve user attributes if success
 *
 * @param object $wiki Main YesWiki object
 * 
 * @return mixed array of user's attribute if logged in, false otherwise
 */
function getCasUser($wiki)
{    
    // Load the CAS lib
    include_once 'tools/login-cas/libs/vendor/CAS/source/CAS.php';
    
    if (isset($_GET['debug'])) {
        // Enable debugging
        phpCAS::setDebug('files/cas.log');
        // Enable verbose error messages. Disable in production!
        phpCAS::setVerbose(true);
    } else {
        phpCAS::setVerbose(false);
    }

    // Initialize phpCAS
    phpCAS::client(CAS_VERSION_2_0, $wiki->config['cas_host'], $wiki->config['cas_port'], $wiki->config['cas_context'], false);    
    
    if (!empty($wiki->config['cas_server_ca_cert_path'])) {
        // For production use set the CA certificate that is the issuer of the cert
        // on the CAS server and uncomment the line below
        phpCAS::setCasServerCACert($wiki->config['cas_server_ca_cert_path']);
    } else {
        // For quick testing you can disable SSL validation of the CAS server.
        // THIS SETTING IS NOT RECOMMENDED FOR PRODUCTION.
        // VALIDATING THE CAS SERVER IS CRUCIAL TO THE SECURITY OF THE CAS PROTOCOL!
        phpCAS::setNoCasServerValidation();
    }
    
    // set the language to french
    phpCAS::setLang(PHPCAS_LANG_FRENCH);    
    phpCAS::forceAuthentication();
    if (phpCAS::isAuthenticated()) {
        return phpCAS::getAttributes();
    } else {
        return false;
    }
}

function userExists($user)
{
    return;
}
function createUser($user)
{
    return;
}
function updateUser($wikiuser, $casuser)
{
    return;
}

function checkConfigCasToBazar($bazar, $firsttime = true)
{
    return ($firsttime && count($bazar)>0 && isset($bazar[0]['id']) && isset($bazar[0]['consent_question']) && count($bazar[0]['fields']) > 0);
}

/**
 * Check if user created an entry
 *
 * @param object $wiki YesWiki main object with config parameters
 * @param string $user Username 
 * 
 * @return string page tag for entry
 */
function bazarEntryExists($wiki, $user)
{
    $res = $wiki->services->get('bazar.fiche.manager')->search(['formsIds' => [$wiki->config['cas_bazar_mapping'][0]['id']], 'user' => $user]);
    return isset($res[0]['tag']) ? $res[0]['tag'] : false;
}

/**
 * Create a bazar entry from selected CAS attributes
 *
 * @param array   $config    yeswiki config for bazar (cas_bazar_mapping)
 * @param array   $user      authentified CAS user attributes
 * @param boolean $anonymous does the user want to be anonymous ?
 * 
 * @return array bazar entry formatted values
 */
function createBazarEntry($config, $user, $anonymous = false)
{
    $fiche = array();
    $fiche['id_typeannonce'] = $config[0]['id'];
    if ($anonymous) {
        // TODO : make it generic
        $fiche['bf_nom'] = substr(trim($user['field_last_name']), 0, 1);
        $fiche['bf_prenom'] = substr(trim($user['field_first_name']), 0, 1);
        $fiche['bf_mail'] = $user['mail'];
        $fiche['bf_titre'] = 'Utilisateur anonyme';
    } else {
        foreach ($config[0]['fields'] as $key => $val) {
            $val = explode('.', $val);
            if (!empty($val[1])) {
                $jsonval = json_decode($user[$val[0]], true);
                // hack for geoloc in bazar..
                if ($val[0] == 'field_lat_lon' && $val[1] == 'latlon') {
                    $jsonval[$val[1]] = isset($jsonval[$val[1]]) ? str_replace(',', '|', $jsonval[$val[1]]) : '';
                }
                $fiche[$key] = isset($jsonval[$val[1]]) ? (string)$jsonval[$val[1]] : '';
            } else {
                $fiche[$key] = isset($user[$val[0]]) ? (string)$user[$val[0]] : '';
            }  
        }
        // if no information about title, we take "first name last name"
        if (!isset($fiche['bf_titre'])) {
            $fiche['bf_titre'] = $fiche['bf_prenom'].' '.$fiche['bf_nom'];
        }
    }
    return $fiche;
}
function updateBazarEntry($fields, $wikiuser, $casuser)
{
    return;
}
