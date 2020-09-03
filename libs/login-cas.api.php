<?php
/**
 * Function library for login
 *
 * @category Wiki
 * @package  YesWiki
 * @author   Florian Schmitt <mrflos@lilo.org>
 * @license  GNU/GPL version 3
 * @link     https://yeswiki.net
 */

/**
 * Get all users or one user's information
 *
 * @param string $username specify username 
 * 
 * @return string json
 */
function getParticipant($username = '')
{
    global $wiki;
    if (!empty($username[0])) {
        $username = urldecode($username[0]);
        $isParticipating = $wiki->loadUserByEmail($username) ? true : false;
        return json_encode([
            'username' => $username,
            'is_participating' => $isParticipating,
        ]);
    } else {
        $users = $wiki->LoadUsers();
        return json_encode(['participant' => $users]);
    }
}

/**
 * Display login api documentation
 *
 * @return void
 */
function documentationlms()
{
    global $wiki;
    $url = $wiki->href('', 'api/participant');
    $output = '<h2>Extension login cas</h2>'."\n".
    'GET <code><a href="'.$url.'">'.$url.'</a></code><br />';
    return $output;
}
