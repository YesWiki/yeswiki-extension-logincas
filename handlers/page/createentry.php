<?php
// Handler /createentry : crée la fiche bazar d'une personne authentifiée par CAS,
// après lui avoir demandé son consentement.

use YesWiki\Bazar\Service\EntryManager;

if (!defined("WIKINI_VERSION")) {
    die("acc&egrave;s direct interdit");
}

require_once 'tools/logincas/libs/login-cas.lib.php';
ob_start();

$request = $this->request;
$auth = logincasDecodeAttributes($request->query->get('attr'));
$bazar = $this->config['cas_bazar_mapping'];

if ($this->GetUser() && $auth) {
    if (!bazarEntryExists($this, $auth['name']) && checkConfigCasToBazar($bazar, $request->query->has('firsttime'))) {
        if ($request->query->has('choice')) {
            $anonymous = $request->query->get('choice') !== 'yes';
            $fiche = createBazarEntry($bazar, $auth, $anonymous);
            if (!empty($fiche)) {
                $fiche['antispam'] = 1;
                $this->services->get(EntryManager::class)->create($fiche['id_typeannonce'], $fiche);
                $this->setMessage('Merci, '.$fiche['bf_titre'].' et bonne navigation sur le wiki !');
                $this->redirect($this->href('', $this->config['root_page']));
            }
        } else {
            $encoded = logincasEncodeAttributes($auth);
            echo '<h1>Création de votre fiche</h1>';
            echo '<div class="consent">'.$bazar[0]['consent_question'];
            echo '<a href="'.$this->href('createentry', '', 'firsttime=1&choice=yes&attr='.$encoded, false).'" class="btn btn-success">OK, j\'accepte !</a> ou
            <a href="'.$this->href('createentry', '', 'firsttime=1&choice=no&attr='.$encoded, false).'" >Non merci</a>';
            echo '</div><br><br>';
        }
    }
} else {
    echo '<div class="alert alert-danger">Erreur : vous n\'êtes pas connecté.</div>';
}

$content = ob_get_clean();
echo $this->Header();
echo $content;
echo $this->Footer();
