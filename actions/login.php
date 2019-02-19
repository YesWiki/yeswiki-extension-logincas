<?php
if (!defined('WIKINI_VERSION')) {
    die('acc&egrave;s direct interdit');
}

// Verification si le fichier de conf est bien renseigné
if (!isset($this->config['cas_host']) or empty($this->config['cas_host'])) {
    echo '<div class="alert alert-danger">'._t('action {{login}} : valeur de l\'url de votre serveur CAS <code>cas_host</code> manquante dans wakka.config.php.<br />Veuillez le renseigner ou supprimer le dossier tools/login-cas pour annuler ce comportement. <a href="https://github.com/YesWiki/yeswiki-extension-login-cas/blob/master/README.md">Lire la documentation technique pour voir toutes les options de configuration</a>').'</div>';
    return;
}

// Lecture des parametres de l'action

// url d'inscription
$signupurl = 'http'.((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

// url du profil
$profileurl = $this->GetParameter('profileurl');

// sauvegarde de l'url d'ou on vient
$incomingurl = $this->GetParameter('incomingurl');
if (empty($incomingurl)) {
    $incomingurl = 'http'.((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
}

$userpage = $this->GetParameter("userpage");
// si pas d'url de page de sortie renseignée, on retourne sur la page courante
if (empty($userpage)) {
    $userpage = $incomingurl;
    // si l'url de sortie contient le passage de parametres de déconnexion, on l'efface
    if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "logout") {
        $userpage = str_replace('&action=logout', '', $userpage);
    }
} else {
    if ($this->IsWikiName($userpage)) {
        $userpage = $this->href('', $userpage);
    }
}

// classe css pour l'action
$class = $this->GetParameter("class");

// classe css pour les boutons
$btnclass = $this->GetParameter("btnclass");
if (empty($btnclass)) {
    $btnclass = 'btn-default';
}
$nobtn = $this->GetParameter("nobtn");

// template par défaut
$template = $this->GetParameter("template");
if (empty($template) || !file_exists('tools/login-cas/presentation/templates/' . $template)) {
    $template = "default.tpl.html";
}

$error = '';
$PageMenuUser = '';

// on initialise la valeur vide si elle n'existe pas
if (!isset($_REQUEST["action"])) {
    $_REQUEST["action"] = '';
}


// Generating the URLS for the local cas example services for proxy testing
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
    $curbase = 'https://' . $_SERVER['SERVER_NAME'];
} else {
    $curbase = 'http://' . $_SERVER['SERVER_NAME'];
}
if ($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) {
    $curbase .= ':' . $_SERVER['SERVER_PORT'];
}

$curdir = dirname($_SERVER['REQUEST_URI']) . "/";

$pgtBase = preg_quote(preg_replace('/^http:/', 'https:', $curbase . $curdir), '/');
$pgtUrlRegexp = '/^' . $pgtBase . '.*$/';

$cas_url = 'https://' . $this->config['cas_host'];
if ($this->config['cas_port'] != '443') {
    $cas_url = $cas_url . ':' . $this->config['cas_port'] ;
}
$cas_url = $cas_url . $this->config['cas_context'];


// Load the CAS lib
require_once 'tools/login-cas/libs/vendor/CAS/source/CAS.php';


// Enable debugging
//phpCAS::setDebug();
// Enable verbose error messages. Disable in production!
phpCAS::setVerbose(false);

// Initialize phpCAS
phpCAS::client(CAS_VERSION_2_0, $this->config['cas_host'], $this->config['cas_port'], $this->config['cas_context']);


if (!empty($this->config['cas_server_ca_cert_path'])) {
    // For production use set the CA certificate that is the issuer of the cert
    // on the CAS server and uncomment the line below
    phpCAS::setCasServerCACert($this->config['cas_server_ca_cert_path']);
} else {
    // For quick testing you can disable SSL validation of the CAS server.
    // THIS SETTING IS NOT RECOMMENDED FOR PRODUCTION.
    // VALIDATING THE CAS SERVER IS CRUCIAL TO THE SECURITY OF THE CAS PROTOCOL!
    phpCAS::setNoCasServerValidation();
}

// set the language to french
phpCAS::setLang(PHPCAS_LANG_FRENCH);


// cas de la déconnexion
if ($_REQUEST["action"] == "logout") {
    $this->LogoutUser();
    phpCAS::logout(array('url' => $incomingurl));
    exit;
}

$auth = phpCAS::isAuthenticated();

if ($auth) {
    $attr = phpCAS::getAttributes();
    $email = isset($attr["mail"]) ? $attr["mail"] : '';
    $nomwiki = isset($attr["name"]) ? $attr["name"] : '';
    $user = $this->LoadUser($nomwiki);
    if ($user) {
        $this->SetUser($user, 1);
    } else {
        $this->Query(
            "insert into ".$this->config["table_prefix"]."users set ".
            "signuptime = now(), ".
            "name = '".mysqli_real_escape_string($this->dblink, $nomwiki)."', ".
            "email = '".mysqli_real_escape_string($this->dblink, $email)."', ".
            "password = md5('".mysqli_real_escape_string($this->dblink, uniqid('cas_'))."')"
        );

        // log in
        $this->SetUser($this->LoadUser($nomwiki));
    }
    $bazar = $this->config['cas_bazar_mapping'];
    if (count($bazar)>0 and isset($bazar[0]['id']) and isset($bazar[0]['consent_question']) and count($bazar[0]['fields']) > 0) {
        //echo $this->Header();
        //echo $bazar[0]['consent_question'];
        //echo $this->Footer();
        //exit;
    }
} else {
    if (isset($_GET['action']) && $_GET['action'] == 'connectCAS') {
        try {
            // force CAS authentication
            phpCAS::forceAuthentication();
        } catch (\Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    } else {
        $this->redirect(str_replace('/?', '/?wiki=', $incomingurl).'&action=connectCAS');
    }
}

// cas d'une personne connectée déjà
if ($user = $this->GetUser()) {
    $connected = true;
    if ($this->LoadPage("PageMenuUser")) {
        $PageMenuUser.= $this->Format("{{include page=\"PageMenuUser\"}}");
    }

    // si pas de pas d'url de profil renseignée, on utilise ParametresUtilisateur
    if (empty($profileurl)) {
        $profileurl = $this->href("", "ParametresUtilisateur", "");
    } elseif ($profileurl == 'WikiName') {
        $profileurl = $this->href("edit", $user['name'], "");
    } else {
        if ($this->IsWikiName($profileurl)) {
            $profileurl = $this->href('', $profileurl);
        }
    }
} else {
    // cas d'une personne non connectée
    $connected = false;

    // si l'authentification passe mais la session n'est pas créée, on a un problème de cookie
    if ($_REQUEST['action'] == 'checklogged') {
        $error = 'Vous devez accepter les cookies pour pouvoir vous connecter.';
    }
}

//
// on affiche le template
//

include_once('includes/squelettephp.class.php');

// on cherche un template personnalise dans le repertoire themes/tools/bazar/templates
$templatetoload = 'themes/tools/login-cas/templates/' . $template;

if (!is_file($templatetoload)) {
    $templatetoload = 'tools/login-cas/presentation/templates/' . $template;
    if (!is_file($templatetoload)) {
        exit('<div class="alert alert-danger">template non trouvé : '.$template.'.</div>');
    }
}

$squel = new SquelettePhp($templatetoload);
$squel->set(
    array(
        "connected" => $connected,
        "user" => ((isset($user["name"])) ? $user["name"] : ((isset($_POST["name"])) ? $_POST["name"] : '')),
        "email" => ((isset($user["email"])) ? $user["email"] : ((isset($_POST["email"])) ? $_POST["email"] : '')),
        "incomingurl" => $incomingurl,
        "signupurl" => $signupurl,
        "profileurl" => $profileurl,
        "userpage" => $userpage,
        "PageMenuUser" => $PageMenuUser,
        "btnclass" => $btnclass,
        "nobtn" => $nobtn,
        "error" => $error
    )
);

$output = (!empty($class)) ? '<div class="'.$class.'">'."\n".$squel->analyser()."\n".'</div>'."\n" : $squel->analyser();

echo $output;
