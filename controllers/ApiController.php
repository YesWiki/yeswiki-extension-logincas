<?php

namespace YesWiki\LoginCas\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use YesWiki\Core\ApiResponse;
#use YesWiki\Core\Service\TripleStore;
use YesWiki\Core\YesWikiController;

class ApiController extends YesWikiController
{
    /**
     * @Route("/api/participant/subscribe/{email}", methods={"GET"})
     */
    public function getUserList($email)
    {
        global $wiki;
        if (!empty($email)) {
            $email = urldecode($email);
            $isParticipating = $wiki->loadUserByEmail($email) ? true : false;
            return new ApiResponse([
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
            return new ApiResponse(['participant' => $filteredUsers]);
        }
    }

    /**
     * @Route("/api/participant/{action}", methods={"POST"})
     */
    public function manageUserParticipation($action)
    {
        global $wiki;
        trigger_error('mrflos action '.$action);
        if (empty($action)) {
            throw new BadRequestHttpException(["message" => "Wrong route for POST, only 'subscribe' or 'unsubscribe'."], 403);
        } elseif ($action == 'subscribe') {
            if (!empty($_POST['email'])) {
                $email = urldecode($_POST['email']);
                $name = urldecode($_POST['name']);
                $isParticipating = $wiki->loadUserByEmail($email) ? true : false;
                if (!$isParticipating) {
                    if (!empty($email) and !empty($name)) {
                        $wiki->Query(
                            "insert into ".$wiki->config["table_prefix"]."users set ".
                            "signuptime = now(), ".
                            "name = '".mysqli_real_escape_string($wiki->dblink, $name)."', ".
                            "email = '".mysqli_real_escape_string($wiki->dblink, $email)."', ".
                            "motto = '',".
                            "password = md5('".mysqli_real_escape_string($wiki->dblink, uniqid('cas_'))."')"
                        );
                        $user = $wiki->LoadUser($name);
                        return new ApiResponse([
                            "name" => $user['name'],
                            "email" => $user['email'],
                        ]);
                    } else {
                        return new ApiResponse(["message" => "username or email missing in POST."], 403);
                    }
                } else {
                    return new ApiResponse(["message" => "A user exists with this email."]);
                }
            } else {
                return new ApiResponse(["message" => "No 'email' found in this post request ."], 403);
            }
        } elseif ($action == 'unsubscribe') {
            $email = urldecode($_POST['email']);
            trigger_error('mrflos '.$email);
            //$name = urldecode($_POST['name']);
            $isParticipating = $wiki->loadUserByEmail($email) ? true : false;
            if ($isParticipating) {
                $wiki->Query(
                    "DELETE FROM ".$wiki->config["table_prefix"]."users WHERE ".
                    "email = '".mysqli_real_escape_string($wiki->dblink, $email)."';"
                );
                return new ApiResponse(['message' => 'User '.$name.' ('.$email.') succesfully deleted.']);
            } else {
                return new ApiResponse(["message" => "No user to delete with this email or name."]);
            }
        } else {
            return new ApiResponse(["message" => "Wrong route for POST, only 'subscribe' or 'unsubscribe'."], 403);
        }
    }

    /**
     * @Route("/api/participant/{email}", methods={"DELETE"})
     */
    public function deleteParticipant($email)
    {
        global $wiki;
        if (!empty($email)) {
            $email = urldecode($email);
            $isParticipating = $wiki->loadUserByEmail($email) ? true : false;
            if ($isParticipating) {
                $wiki->Query(
                    "DELETE FROM ".$wiki->config["table_prefix"]."users WHERE ".
                    "email = '".mysqli_real_escape_string($wiki->dblink, $email)."';"
                );
                return new ApiResponse(['message' => 'User '.$email.' succesfully deleted.']);
            } else {
                return new ApiResponse(["message" => "No user to delete with this email or name."]);
            }
        }
    }

    /**
     * Display login api documentation
     *
     * @return void
     */
    public function getDocumentation()
    {
        global $wiki;
        $url = $wiki->href('', 'api/participant');
        $output = '<h2>Extension login cas</h2>'."\n".
        'GET <code><a href="'.$url.'">'.$url.'</a></code><br />';
        return $output;
    }
}
