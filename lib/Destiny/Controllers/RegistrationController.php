<?php
namespace Destiny\Controllers;

use Destiny\Common\Application;
use Destiny\Common\Log;
use Destiny\Common\Utils\Country;
use Destiny\Common\ViewModel;
use Destiny\Common\Session;
use Destiny\Common\Exception;
use Destiny\Common\Request;
use Destiny\Common\Authentication\AuthenticationCredentials;
use Destiny\Common\Authentication\AuthenticationRedirectionFilter;
use Destiny\Common\Annotation\Controller;
use Destiny\Common\Annotation\Route;
use Destiny\Common\Annotation\HttpMethod;
use Destiny\Common\Authentication\AuthenticationService;
use Destiny\Common\User\UserService;
use Destiny\Google\GoogleRecaptchaHandler;
use Doctrine\DBAL\DBALException;

/**
 * @Controller
 */
class RegistrationController {

    /**
     * Make sure we have a valid session
     *
     * @param array $params
     * @throws Exception
     * @return AuthenticationCredentials
     */
    private function getSessionAuthenticationCredentials(array $params) {
        if (!isset ($params ['code']) || empty ($params ['code'])) {
            throw new Exception ('Invalid code');
        }
        $authSession = Session::get('authSession');
        if ($authSession instanceof AuthenticationCredentials) {
            if (empty ($authSession) || ($authSession->getAuthCode() != $params ['code'])) {
                throw new Exception ('Invalid authentication code');
            }
            if (!$authSession->isValid()) {
                throw new Exception ('Invalid authentication information');
            }
        } else {
            throw new Exception ('Could not retrieve session data. Possibly due to cookies not being enabled.');
        }
        return $authSession;
    }

    /**
     * @Route ("/register")
     * @HttpMethod ({"GET"})
     *
     * Handle the confirmation request
     *
     * @param array $params
     * @param ViewModel $model
     * @return string
     * @throws Exception
     */
    public function register(array $params, ViewModel $model) {
        $authCreds = $this->getSessionAuthenticationCredentials($params);
        $email = $authCreds->getEmail();
        $username = $authCreds->getUsername();
        if (!empty ($username) && empty ($email)) {
            $email = $username . '@destiny.gg';
        }
        $model->title = 'Register';
        $model->username = $username;
        $model->email = $email;
        $model->follow = (isset($params['follow'])) ? $params['follow'] : '';
        $model->authProvider = $authCreds->getAuthProvider();
        $model->code = $authCreds->getAuthCode();
        $model->rememberme = Session::get('rememberme');
        return 'register';
    }

    /**
     * @Route ("/register")
     * @HttpMethod ({"POST"})
     *
     * @param array $params
     * @param ViewModel $model
     * @param Request $request
     * @return string
     *
     * @throws DBALException
     * @throws Exception
     */
    public function registerProcess(array $params, ViewModel $model, Request $request) {
        $userService = UserService::instance();
        $authService = AuthenticationService::instance();
        $authCreds = $this->getSessionAuthenticationCredentials($params);

        $username = (isset ($params ['username']) && !empty ($params ['username'])) ? $params ['username'] : '';
        $email = (isset ($params ['email']) && !empty ($params ['email'])) ? $params ['email'] : '';
        $country = (isset ($params ['country']) && !empty ($params ['country'])) ? $params ['country'] : '';

        $authCreds->setUsername($username);
        $authCreds->setEmail($email);

        try {
            if (!isset($params['g-recaptcha-response']) || empty($params['g-recaptcha-response']))
                throw new Exception ('You must solve the recaptcha.');
            $googleRecaptchaHandler = new GoogleRecaptchaHandler();
            $googleRecaptchaHandler->resolve($params['g-recaptcha-response'], $request);
            $authService->validateUsername($username);
            if ($userService->getIsUsernameTaken($username, -1)) {
                throw new Exception ('The username you asked for is already being used');
            }
            $authService->validateEmail($email);
            if (!empty ($country)) {
                $countryArr = Country::getCountryByCode($country);
                if (empty ($countryArr)) {
                    throw new Exception ('Invalid country');
                }
                $country = $countryArr ['alpha-2'];
            }
        } catch (Exception $e) {
            $model->title = 'Register Error';
            $model->username = $username;
            $model->email = $email;
            $model->follow = (isset($params['follow'])) ? $params['follow'] : '';
            $model->authProvider = $authCreds->getAuthProvider();
            $model->code = $authCreds->getAuthCode();
            $model->error = $e;
            return 'register';
        }

        $conn = Application::getDbConn();
        try {
            $conn->beginTransaction();
            $userId = $userService->addUser([
                'username' => $username,
                'email' => $email,
                'userStatus' => 'Active',
                'country' => $country
            ]);
            $userService->addUserAuthProfile([
                'userId' => $userId,
                'authProvider' => $authCreds->getAuthProvider(),
                'authId' => $authCreds->getAuthId(),
                'authCode' => $authCreds->getAuthCode(),
                'authDetail' => $authCreds->getAuthDetail(),
                'refreshToken' => $authCreds->getRefreshToken()
            ]);
            $conn->commit();
            Session::remove('authSession');
        } catch (DBALException $e) {
            $n = new Exception("Failed to insert user records", $e);
            Log::critical($n);
            $conn->rollBack();
            throw $n;
        }

        if (isset ($params ['rememberme']) && !empty ($params ['rememberme'])) {
            Session::set('rememberme', $params ['rememberme']);
        }
        if (isset ($params ['follow']) && !empty ($params ['follow'])) {
            Session::set('follow', $params ['follow']);
        }

        $authCredHandler = new AuthenticationRedirectionFilter ();
        return $authCredHandler->execute($authCreds);
    }

}