<?php
// Action {{login}} de l'extension logincas. Elle remplace le login natif de
// YesWiki quand l'extension est installée, et se replie dessus dès que le
// serveur CAS n'est pas configuré ou ne répond pas.

if (!defined('WIKINI_VERSION')) {
    die('acc&egrave;s direct interdit');
}

require_once 'tools/logincas/libs/login-cas.lib.php';

if (empty($this->config['cas_host'])) {
    echo '<div class="alert alert-warning">'
        . _t('CAS_CONFIG_MISSING') . ' <code>cas_host</code>. '
        . _t('CAS_FALLBACK_NOTICE')
        . ' <a href="' . logincasDocUrl($this) . '">' . _t('CAS_READ_DOC') . '</a>'
        . '</div>';
    echo logincasRenderCoreLogin($this);

    return;
}

$signupurl = 'http'.((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

$profileurl = $this->GetParameter('profileurl');

$class = $this->GetParameter("class");

$btnclass = $this->GetParameter("btnclass");
if (empty($btnclass)) {
    $btnclass = 'btn-default';
}
$nobtn = $this->GetParameter("nobtn");

$template = $this->GetParameter("template");
if (empty($template) || !file_exists('tools/logincas/presentation/templates/' . $template)) {
    $template = "default.tpl.html";
}

$error = '';
$PageMenuUser = '';

if (!isset($_REQUEST["action"])) {
    $_REQUEST["action"] = '';
}

$incomingurl = $this->GetParameter('incomingurl');
if (empty($incomingurl)) {
    $incomingurl = 'http'.((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
}

$userpage = $this->GetParameter("userpage");
if (empty($userpage)) {
    $userpage = $incomingurl;
    if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "logout") {
        $userpage = str_replace('&action=logout', '', $userpage);
    }
} else {
    if ($this->IsWikiName($userpage)) {
        $userpage = $this->href('', $userpage);
    }
}

if ($_REQUEST["action"] == "logout") {
    $this->LogoutUser();
    $incomingurl = str_replace(array('wiki=', '&action=logout'), '', $incomingurl);
    $this->redirect($incomingurl);
}

if ($_REQUEST['action'] == 'connectCAS') {
    if (!isset($_GET['ticket'])) {
        setcookie("PHPSESSID", "", time()-3600, "/");
    }
    try {
        $attr = getCasUser($this);
    } catch (Throwable $exception) {
        echo '<div class="alert alert-warning">'
            . _t('CAS_SERVER_UNREACHABLE') . ' ' . _t('CAS_FALLBACK_NOTICE')
            . '</div>';
        echo logincasRenderCoreLogin($this);

        return;
    }

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

        $bazar = $this->config['cas_bazar_mapping'];
        $entry = bazarEntryExists($this, $user['name']);
        if (!$entry && checkConfigCasToBazar($bazar)) {
            $this->redirect($this->href('createentry', 'BazaR', 'firsttime=1&attr='.logincasEncodeAttributes($attr), false));
        } elseif ($entry && checkConfigCasToBazar($bazar, !isset($_GET['firsttime']))) {
            $this->SetMessage('');
        }
    
        $incomingurl = str_replace(array('wiki=','&action=connectCAS'), '', $incomingurl);
        $this->redirect($incomingurl);
    } else {
        echo '<div class="alert alert-warning">'
            . _t('CAS_AUTHENTICATION_FAILED') . ' ' . _t('CAS_FALLBACK_NOTICE')
            . '</div>';
        echo logincasRenderCoreLogin($this);

        return;
    }
}

if ($user = $this->GetUser()) {
    $connected = true;
    if ($this->LoadPage("PageMenuUser")) {
        $PageMenuUser.= $this->Format("{{include page=\"PageMenuUser\"}}");
    }
    
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
    $connected = false;
    
    if (!strstr($incomingurl, '/?wiki=')) {
        $incomingurl = str_replace('/?', '/?wiki=', $incomingurl);
    }

    if ($_REQUEST['action'] == 'checklogged') {
        $error = _t('CAS_COOKIES_REQUIRED');
    }
}

require_once 'includes/squelettephp.class.php';

$squel = new SquelettePhp($template, 'logincas');
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
