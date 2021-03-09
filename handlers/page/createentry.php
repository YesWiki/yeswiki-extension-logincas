<?php
/**
 * Handler for creating bazar entry based on CAS server informations
 *
 * @category YesWiki
 * @package  Logincas
 * @author   Florian Schmitt <mrflos@lilo.org>
 * @license  https://www.gnu.org/licenses/agpl-3.0.en.html AGPL 3.0
 * @link     https://yeswiki.net
 */

use YesWiki\Bazar\Service\EntryManager;

// Vérification de sécurité
if (!defined("WIKINI_VERSION")) {
    die("acc&egrave;s direct interdit");
}

require_once 'tools/logincas/libs/login-cas.lib.php';
ob_start();
$auth = unserialize(rawurldecode($_GET['attr']));
$bazar = $this->config['cas_bazar_mapping'];
if ($this->GetUser() && isset($_GET['attr']) && $auth) {
    if (!bazarEntryExists($this, $auth['name']) && checkConfigCasToBazar($bazar, isset($_GET['firsttime']))) {
        if (isset($_GET['choice'])) {
            $anonymous = $_GET['choice']=='yes' ? false : true;
            $fiche = createBazarEntry($bazar, $auth, $anonymous);
            if (!empty($fiche)) {
                $fiche['antispam'] = 1;
                $GLOBALS['wiki']->services->get(EntryManager::class)->create($fiche['id_typeannonce'], $fiche);
                var_dump($fiche, $auth);
                exit;
                //$fiche = baz_insertion_fiche($fiche);
                $this->setMessage('Merci, '.$fiche['bf_titre'].' et bonne navigation sur le mooc Transition Intérieure !');
                $this->redirect($this->href('', $this->config['root_page']));
            }
        } else {
            echo '<h1>Création de votre fiche</h1>';
            echo '<div class="consent">'.$bazar[0]['consent_question'];
            echo '<a href="'.$this->href('createentry', '', 'firsttime=1&choice=yes&attr='.rawurlencode(serialize($auth)), false).'" class="btn btn-success">OK, j\'accepte !</a> ou 
            <a href="'.$this->href('createentry', '', 'firsttime=1&choice=no&attr='.rawurlencode(serialize($auth)), false).'" >Non merci</a>';
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
