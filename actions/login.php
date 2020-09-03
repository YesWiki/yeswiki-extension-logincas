<?php
/**
 * Action for login
 *
 * @category YesWiki
 * @package  Login-cas
 * @author   Florian Schmitt <mrflos@lilo.org>
 * @license  https://www.gnu.org/licenses/agpl-3.0.en.html AGPL 3.0
 * @link     https://yeswiki.net
 */

if (!defined('WIKINI_VERSION')) {
    die('acc&egrave;s direct interdit');
}

require_once 'tools/login-cas/libs/login-cas.lib.php';

// Verification si le fichier de conf est bien renseigné
if (!isset($this->config['cas_host']) or empty($this->config['cas_host'])) {
    echo '<div class="alert alert-danger">'._t('action {{login}} : valeur de l\'url de votre serveur CAS <code>cas_host</code> manquante dans wakka.config.php.<br />Veuillez le renseigner pour utiliser cette extension. <a href="https://github.com/YesWiki/yeswiki-extension-login-cas/blob/master/README.md">Lire la documentation technique pour voir toutes les options de configuration</a>').'</div>';
    include_once 'tools/login/actions/login.php';
    return;
}

// Lecture des parametres de l'action

// url d'inscription
$signupurl = 'http'.((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

// url du profil
$profileurl = $this->GetParameter('profileurl');

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

// cas de la déconnexion
if ($_REQUEST["action"] == "logout") {
    $this->LogoutUser();
    $incomingurl = str_replace(array('wiki=', '&action=logout'), '', $incomingurl);
    //TODO trouver comment se deconnecter que tu service phpCAS::logout(array('url' => $incomingurl));
    $this->redirect($incomingurl);
}

// demande de connexion
if ($_REQUEST['action'] == 'connectCAS') {
    // on vide les cookie pour la premiere connexion
    if (!isset($_GET['ticket'])) {
        setcookie("PHPSESSID", "", time()-3600, "/"); // delete session cookie
    }
    $attr = getCasUser($this);

    if ($attr) {
        $email = isset($attr["mail"]) ? $attr["mail"] : '';
        $nomwiki = isset($attr["name"]) ? $attr["name"] : '';
        $user = $this->LoadUser($nomwiki);
        if (!$user) {
            $this->Query(
                "insert into ".$this->config["table_prefix"]."users set ".
                "signuptime = now(), ".
                "name = '".mysqli_real_escape_string($this->dblink, $nomwiki)."', ".
                "email = '".mysqli_real_escape_string($this->dblink, $email)."', ".
                "motto = '',".
                "password = md5('".mysqli_real_escape_string($this->dblink, uniqid('cas_'))."')"
            );
            // log in
            $user = $this->LoadUser($nomwiki);
        }
        $this->SetUser($user, 1);

        // cas de l'option creation de fiche bazar a la connexion
        $bazar = $this->config['cas_bazar_mapping'];
        $entry = bazarEntryExists($this, $user['name']);
        if (!$entry && checkConfigCasToBazar($bazar)) {
            $this->redirect($this->href('createentry', 'BazaR', 'firsttime=1&attr='.rawurlencode(serialize($attr)), false));
        } elseif ($entry && checkConfigCasToBazar($bazar, !isset($_GET['firsttime']))) {
            //update if necessary
        }
    
        $incomingurl = str_replace(array('wiki=','&action=connectCAS'), '', $incomingurl);
        $this->redirect($incomingurl);
    } else {
        echo '<div class="alert alert-danger">Erreur d\'authentification sur le serveur CAS</div>';
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
    
    // on rajoute le wiki= pour le serveur CAS
    if (!strstr($incomingurl, '/?wiki=')) {
        $incomingurl = str_replace('/?', '/?wiki=', $incomingurl);
    }

    // si l'authentification passe mais la session n'est pas créée, on a un problème de cookie
    if ($_REQUEST['action'] == 'checklogged') {
        $error = 'Vous devez accepter les cookies pour pouvoir vous connecter.';
    }
}

//
// on affiche le template
//
require_once 'includes/squelettephp.class.php';

$squel = new SquelettePhp($template, 'login-cas');
$output = $squel->render(
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
$output = (!empty($class)) ? '<div class="'.$class.'">'."\n".$output."\n".'</div>'."\n" : $output;
echo $output;
