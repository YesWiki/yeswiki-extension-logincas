<?php
/**
 * Handler for creating bazar entry based on CAS server informations
 * 
 * @category YesWiki
 * @package  Login-cas
 * @author   Florian Schmitt <mrflos@lilo.org>
 * @license  https://www.gnu.org/licenses/agpl-3.0.en.html AGPL 3.0
 * @link     https://yeswiki.net
 */

// Vérification de sécurité
if (!defined("WIKINI_VERSION")) {
    die("acc&egrave;s direct interdit");
}

require_once 'tools/login-cas/libs/login-cas.lib.php';
ob_start();
$auth = unserialize(rawurldecode($_GET['attr']));
$bazar = $this->config['cas_bazar_mapping'];
if ($this->GetUser() && isset($_GET['attr']) && $auth) {
    if (!bazarEntryExists($this, $auth['name']) && checkConfigCasToBazar($bazar, isset($_GET['firsttime']))) {
        if (isset($_GET['choice'])) {
            $anonymous = $_GET['choice']=='yes' ? false : true;
            $fiche = createBazarEntry($bazar, $auth, $anonymous);
            if (count($fiche>0)) {
                include_once 'tools/bazar/libs/bazar.fonct.php';
                $fiche = baz_insertion_fiche($fiche);
                $this->redirect($this->href('', $fiche['id_fiche']));
            }
        } else {
            echo '<h1>Création de votre fiche</h1>';
            echo '<div class="consent">'.$bazar[0]['consent_question'];
            echo '<a href="'.$this->href('createentry', '', 'firsttime=1&choice=yes&attr='.rawurlencode(serialize($auth)), false).'" class="btn btn-primary">OK, j\'accepte !</a> ou 
            <a href="'.$this->href('createentry', '', 'firsttime=1&choice=no&attr='.rawurlencode(serialize($auth)), false).'" class="btn btn-default">Non merci</a>';
            echo '</div><br><br>';
        }
    }
} else {
    echo '<div class="alert alert-danger">Erreur : nous n\'etes pas connecté.</div>';
}

$content = ob_get_clean();
echo $this->Header();
echo $content;
echo $this->Footer();