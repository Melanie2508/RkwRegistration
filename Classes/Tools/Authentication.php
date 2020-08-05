<?php

namespace RKW\RkwRegistration\Tools;

use \RKW\RkwBasics\Helper\Common;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use RKW\RkwBasics\Service\CookieService;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Class Authenticate
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwRegistration
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Authentication implements \TYPO3\CMS\Core\SingletonInterface
{

    /**
     * Length of token for anonymous users
     *
     * @const integer
     * @see \RKW\RkwRegistration\Tools\Registration::ANONYMOUS_TOKEN_LENGTH
     */
    const ANONYMOUS_TOKEN_LENGTH = 20;

    /**
     * FrontendUserRepository
     *
     * @var \RKW\RkwRegistration\Domain\Repository\FrontendUserRepository
     */
    protected $frontendUserRepository;


    /**
     * Persistence Manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    protected $persistenceManager;


    /**
     * Setting
     *
     * @var array
     */
    protected $settings;


    /**
     * Validates the given username/password combination against the saved user data
     *
     * @param string $username
     * @param string $password
     * @return \RKW\RkwRegistration\Domain\Model\FrontendUser | integer
     * @throws \RKW\RkwRegistration\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function validateUser($username, $password)
    {

        $settings = $this->getSettings();
        $status = 1;

        if (!$username) {
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, sprintf('No valid username given.'));
            throw new \RKW\RkwRegistration\Exception('No valid username given.', 1435035135);
            //===
        }

        if (!$password) {
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, sprintf('No valid password given.'));
            throw new \RKW\RkwRegistration\Exception('No valid password given.', 1435035166);
            //===
        }

        if (
            ($user = $this->getFrontendUserRepository()->findOneByUsername(strtolower(trim($username))))
            && ($user instanceof \RKW\RkwRegistration\Domain\Model\FrontendUser)
            && ($user->getPassword())
        ) {

            // check for salted passwords
            if (
                (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('saltedpasswords'))
                && (\TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::isUsageEnabled('FE'))
            ) {

                $objSalt = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance($user->getPassword());
                if (is_object($objSalt)) {

                    if ($objSalt->checkPassword($password, $user->getPassword())) {

                        // reset counter
                        $user->setTxRkwregistrationLoginErrorCount(0);
                        $this->getFrontendUserRepository()->update($user);

                        $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Successfully authentificated user "%s" using a salted password.', strtolower(trim($username))));

                        return $user;
                        //===
                    }
                }

                // check for plaintext passwords
            } else {
                if ($password == $user->getPassword()) {

                    // reset counter
                    $user->setTxRkwregistrationLoginErrorCount(0);
                    $this->getFrontendUserRepository()->update($user);

                    $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Successfully authenticated user "%s" using a plaintext password.', strtolower(trim($username))));

                    return $user;
                    //===
                }
            }

            // increment counter
            $user->incrementTxRkwregistrationLoginErrorCount();

            // check max error counter
            $maxLoginErrors = 10;
            if (intval($settings['users']['maxLoginErrors']) > 0) {
                $maxLoginErrors = intval($settings['users']['maxLoginErrors']);
            }

            // disable user if maxLoginErrors is reached
            if ($user->getTxRkwregistrationLoginErrorCount() >= $maxLoginErrors) {
                $user->setDisable(1);
                $status = 2;
                $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, sprintf('Disabled user "%s", because of too many authentication failures.', strtolower(trim($username))));
            }

            $this->getFrontendUserRepository()->update($user);
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, sprintf('Authentication failed for user "%s".', strtolower(trim($username))));

            return $status;
        }

        $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, sprintf('User "%s" not found.', strtolower(trim($username))));
        return 0;
    }


    /**
     * Checks the given token of an anonymous user
     *
     * @param string $token
     * @return \RKW\RkwRegistration\Domain\Model\FrontendUser| boolean
     */
    public function validateAnonymousUser($token)
    {

        /** @var \RKW\RkwRegistration\Domain\Repository\FrontendUserRepository $frontendUserRepository */
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $frontendUserRepository = $objectManager->get('RKW\\RkwRegistration\\Domain\\Repository\\FrontendUserRepository');

        // check if given token exists and has the expected length!
        if (
            (strlen($token) == self::ANONYMOUS_TOKEN_LENGTH)
            && ($anonymousUser = $frontendUserRepository->findOneByToken($token))
        ) {

            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Successfully authenticated anonymous user with token "%s".', trim($token)));

            return $anonymousUser;

        }

        $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, sprintf('Anonymous user with token "%s" not found.', trim($token)));

        return false;
    }

    /**
     * Validates a social media user
     *
     * @param $frontendUser \RKW\RkwRegistration\Domain\Model\FrontendUser
     * @return \RKW\RkwRegistration\Domain\Model\FrontendUser| boolean
     */
    public function validateSocialMediaUser($frontendUser)
    {

        /** @var \RKW\RkwRegistration\Domain\Repository\FrontendUserRepository $frontendUserRepository */
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $frontendUserRepository = $objectManager->get('RKW\\RkwRegistration\\Domain\\Repository\\FrontendUserRepository');

        // check if user exists
        if ($databaseFrontendUser = $frontendUserRepository->findUser($frontendUser)) {

            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Successfully authenticated social media user with username "%s".', strtolower(trim($frontendUser->getUsername()))));

            return $databaseFrontendUser;
        }

        $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, sprintf('Anonymous user with token "%s" not found.', strtolower(trim($frontendUser->getUsername()))));

        return false;

    }


    /**
     * Checks random token which allows a cross domain login
     *
     * @param string $token
     * @return \RKW\RkwRegistration\Domain\Model\FrontendUser|boolean
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function validateCrossDomainLoginToken($token)
    {

        $settings = $this->getSettings();
        $validTime = (intval($settings['crossDomainLoginValidTime']) ? intval($settings['crossDomainLoginValidTime']) : 60);

        $result = false;
        if ($domain = $_SERVER['SERVER_NAME']) {

            $generatedToken = sha1($token . $domain);
            /** @var \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser */
            if ($frontendUser = $this->getFrontendUserRepository()->findOneByTxRkwregistrationCrossDomainToken($generatedToken)) {

                // check if token is still valid
                if ((intval($frontendUser->getTxRkwregistrationCrossDomainTokenTstamp()) + $validTime) > time()) {
                    $result = $frontendUser;
                }

                // delete token - we only use it one time
                $frontendUser->setTxRkwregistrationCrossDomainToken('');
                $frontendUser->setTxRkwregistrationCrossDomainTokenTstamp(0);
                $this->getFrontendUserRepository()->update($frontendUser);
                $this->getPersistenceManager()->persistAll();
            }

        }

        if ($result) {
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Successfully authenticated user via cross-domain token "%s".', $token));
        } else {
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, sprintf('Authentication via cross-domain token "%s" failed.', $token));
        }

        return $result;
    }


    /**
     * Generates a random token for a logged in user which allows a cross domain login
     *
     * @param string $url
     * @return null|string
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function setCrossDomainLoginToken($url)
    {

        // generate random token
        $characters = '23456789abcdefghjkmnopqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ';
        $randomString = substr(str_shuffle($characters), 0, 30);

        // check if there is a valid redirect domain and the user is logged in!
        /**  @var $feAuth \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication */
        if (
            ($feAuth = $GLOBALS['TSFE']->fe_user)
            && ($frontendUser = $this->getFrontendUserRepository()->findByIdentifier($feAuth->user['uid']))
            && ($domain = $this->getDomain($url))
        ) {

            // add username and do sha1
            $token = sha1($randomString . $frontendUser->getUsername());

            // save token with domain as sha1, but return it without domain - this allows us to check the domain afterwards
            $frontendUser->setTxRkwregistrationCrossDomainToken(sha1($token . $domain));
            $frontendUser->setTxRkwregistrationCrossDomainTokenTstamp(time());
            $this->getFrontendUserRepository()->update($frontendUser);
            $this->getPersistenceManager()->persistAll();

            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Successfully generated cross domain login token for user with id %s.', $feAuth->user['uid']));

            return $token;

        }

        $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, sprintf('Could not generat cross domain login token.'));

        return null;

    }

    /**
     * Sets a temporary session cookie with the user-id
     * IMPORTANT: After a redirect the user is logged in then
     * DANGER: This method authenticates the given user without checking for password!!!
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\FrontendUser $frontendUser
     * @return void
     * @throws \RKW\RkwRegistration\Exception
     */
    public static function loginUser(\TYPO3\CMS\Extbase\Domain\Model\FrontendUser $frontendUser, $password = "max")
    {
        // @toDo: We need to set the cleartext password in the RegistrationController (also on redirect logins)

        if (!$frontendUser->getUid()) {
            throw new \RKW\RkwRegistration\Exception('No valid uid for user given.', 1435002338);
        }

        $userArray = array(
            'uid' => $frontendUser->getUid()
        );

        $version = VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version);
        if ($version >=  8000000) {

            /*
             getUserFE -> AbstractUserAuthentication


             */


            /*
            $GLOBALS['TSFE']->fe_user->start(); // set cookie and initiate login
            $GLOBALS['TSFE']->fe_user->createUserSession($userArray);  // create user session in database
            $GLOBALS['TSFE']->fe_user->user = $GLOBALS['TSFE']->fe_user->fetchUserSession(); // get user session from database
            $GLOBALS['TSFE']->fe_user->loginSessionStarted = true; // set session as started equal to a successful login
            $GLOBALS['TSFE']->initUserGroups(); // Initializes the front-end user groups based on all fe_groups records that the current fe_user is member of
            $GLOBALS['TSFE']->loginUser = true; //  Global flag indicating that a frontend user is logged in. Should already by set by \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::initUserGroups();
            $GLOBALS['TSFE']->storeSessionData(); // store session in database
            */

            $GLOBALS['TSFE']->storeSessionData();

            // Bei TYPO3 Version 9 erfolgt der Login automatisch über die übermittelten POST-Felder (logintype, user, pass) mittels des Authentication-Services.
            $_POST['logintype'] = 'login';
            $_POST['user'] = $frontendUser->getEmail();
            $_POST['pass'] = $password;

            // $authService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\RKW\RkwRegistration\Service\FrontendUserAuthService::class);
            /** @var \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication $authService */
            $authService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication::class);
            $authService->start();

            /*
            $authService->is_permanent = 1;
            $authService->checkPid = 0;
            $authService->dontSetCookie = false;
            $authService->loginSessionStarted = true;
            */

            // it seems that we have subsequently the problem, that we need a logged user now
            // (may be a standard cookie Problem: Is set after new page load)
            // so copy the authentication now to TSFE (is the same thing)
            // We need this because an error occured in setCrossDomainLoginToken (there we cannot read the userId)
            $GLOBALS['TSFE']->fe_user = $authService;


            /*
            $authService->createUserSession($userArray);
            $authService->user = $GLOBALS['TSFE']->fe_user->fetchUserSession();
            $authService->fetchGroupData();
            //DebuggerUtility::var_dump($authService); exit;
            $GLOBALS['TSFE']->loginUser = true;
            */

            // re-set data for redirect
            CookieService::copyCookieDataToFeUserSession();

        } else {
            /** @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $GLOBALS['TSFE']*/
            /** @var \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication $GLOBALS['TSFE']->fe_user */
            $GLOBALS['TSFE']->fe_user->is_permanent = 0; //set 1 for a permanent cookie, 0 for session cookie
            $GLOBALS['TSFE']->fe_user->checkPid = 0;
            $GLOBALS['TSFE']->fe_user->dontSetCookie = false;
            $GLOBALS['TSFE']->fe_user->createUserSession($userArray);
            $GLOBALS['TSFE']->fe_user->user = $GLOBALS['TSFE']->fe_user->fetchUserSession();
            $GLOBALS['TSFE']->fe_user->fetchGroupData();
            $GLOBALS['TSFE']->loginUser = true;

            // set a dummy cookie
            $GLOBALS['TSFE']->fe_user->setAndSaveSessionData('dummy', true);
        }

        self::getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Logging in User "%s" with uid %s.', strtolower($frontendUser->getUsername()), $frontendUser->getUid()));
    }


    /**
     * Checks if user is logged in
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\FrontendUser $frontendUser
     * @return boolean
     */
    public static function isUserLoggedIn(\TYPO3\CMS\Extbase\Domain\Model\FrontendUser $frontendUser)
    {

        // check which id is logged in and compare it with given user
        if (
            ($GLOBALS['TSFE'])
            && ($GLOBALS['TSFE']->loginUser)
            && ($GLOBALS['TSFE']->fe_user->user['uid'])
        ) {
            if ($frontendUser->getUid() == intval($GLOBALS['TSFE']->fe_user->user['uid'])) {
                return true;
            }
        }

        return false;
    }


    /**
     * Logout
     *
     * @return void
     */
    public static function logoutUser()
    {
        self::getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Logging out user with uid %s.', intval($GLOBALS['TSFE']->fe_user->user['uid'])));
        $GLOBALS['TSFE']->fe_user->removeSessionData();
        $GLOBALS['TSFE']->fe_user->logoff();

        // same like in login action: We have to reset data we need for further (multi-)domain logouts
        $version = VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version);
        if ($version >=  8000000) {
            // re-set url for further logouts
            CookieService::copyCookieDataToFeUserSession();
        }
    }


    /**
     * Extracts the domain from the given url
     *
     * @param string $url
     * @return string | NULL
     */
    protected function getDomain($url)
    {

        $match = array();
        if (preg_match('#^http(s)?://([[:alnum:]._-]+)/#', $url, $match)) {
            return $match[2];
        }

        return null;
    }


    /**
     * Returns logger instance
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    public static function getLogger()
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger(__CLASS__);
    }


    /**
     * Returns FrontendUserRepository
     *
     * @return \RKW\RkwRegistration\Domain\Repository\FrontendUserRepository
     */
    protected function getFrontendUserRepository()
    {

        if (!$this->frontendUserRepository) {
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
            $this->frontendUserRepository = $objectManager->get('RKW\\RkwRegistration\\Domain\\Repository\\FrontendUserRepository');
        }

        return $this->frontendUserRepository;
    }


    /**
     * Returns PersistenceManager
     *
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    protected function getPersistenceManager()
    {

        if (!$this->persistenceManager) {
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
            $this->persistenceManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager');
        }

        return $this->persistenceManager;
    }


    /**
     * Returns TYPO3 settings
     *
     * @return array
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    protected function getSettings()
    {

        if (!$this->settings) {
            $this->settings = Common::getTyposcriptConfiguration('Rkwregistration');
        }


        if (!$this->settings) {
            return array();
        }

        return $this->settings;
    }


}