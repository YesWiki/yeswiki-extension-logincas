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
 * @param string $email specify username 
 * 
 * @return string json
 */
function getParticipant($email = '')
{
    global $wiki;
    if (!empty($email[0])) {
        $email = urldecode($email[0]);
        $isParticipating = $wiki->loadUserByEmail($email) ? true : false;
        return json_encode([
            'email' => $email,
            'is_participating' => $isParticipating,
        ]);
    } else {
        $users = $wiki->LoadUsers();
        $filteredUsers = [];
        $i = 0;
        foreach ($users as $user) {
            $filteredUsers[$i]['email'] = $user['email'] ;
            $filteredUsers[$i]['name'] = $user['name'] ;
            $i++;
        }
        return json_encode(['participant' => $filteredUsers]);
    }
}

function postParticipant($arg = '')
{
    global $wiki;
    header("Access-Control-Allow-Origin: * ");
    header("Content-Type: application/json; charset=UTF-8");

    if (isset($arg[0]) && $arg[0] == 'subscribe') {
        if (!empty($_POST['data']->mail)) {
            $email = urldecode($_POST['data']->mail);
            $isParticipating = $wiki->loadUserByEmail($email) ? true : false;
            if (!$isParticipating) {
                if (!empty($_POST['data']->mail) and !empty($_POST['data']->name)) {
                    $wiki->Query(
                        "insert into ".$wiki->config["table_prefix"]."users set ".
                        "signuptime = now(), ".
                        "name = '".mysqli_real_escape_string($wiki->dblink, $_POST['data']->name)."', ".
                        "email = '".mysqli_real_escape_string($wiki->dblink, $_POST['data']->mail)."', ".
                        "motto = '',".
                        "password = md5('".mysqli_real_escape_string($wiki->dblink, uniqid('cas_'))."')"
                    );
                    $user = $wiki->LoadUser($_POST['data']->name);
                    return json_encode(array(
                        "name" => $user['name'],
                        "email" => $user['email'],
                    ));
                } else {
                    http_response_code(403);
                    return json_encode(array("message" => "username or email missing in POST."));
                }
            } else {
                http_response_code(200);
                return json_encode(array("message" => "A user exists with this email."));
            }
        } else {
            http_response_code(403);
            return json_encode(array("message" => "No 'data' found in this post request ."));
        }
    } else {
        http_response_code(403);
        return json_encode(array("message" => "Wrong route for POST, only 'subscribe'."));
    }
}

function deleteParticipant($email = '')
{
    global $wiki;
    if (!empty($email[0])) {
        $email = urldecode($email[0]);
        $isParticipating = $wiki->loadUserByEmail($email) ? true : false;
        if ($isParticipating) {
            $wiki->Query(
                "DELETE FROM ".$wiki->config["table_prefix"]."users WHERE ".
                "email = '".mysqli_real_escape_string($wiki->dblink, $email)."';"
            );
        } else {
            http_response_code(200);
            header("Access-Control-Allow-Origin: * ");
            header("Content-Type: application/json; charset=UTF-8");
            return json_encode(array("message" => "No user to delete with this email."));
        }
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
