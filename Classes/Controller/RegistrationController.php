<?php

namespace RKW\RkwRegistration\Controller;

use RKW\RkwRegistration\Tools\Password;
use RKW\RkwRegistration\Tools\Authentication;
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
 * Class RegistrationController
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwRegistration
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RegistrationController extends ControllerAbstract
{
    /**
     * Signal name for use in ext_localconf.php
     *
     * @const string
     */
    const SIGNAL_AFTER_USER_PASSWORD_RESET = 'afterUserPasswordReset';


    /**
     * RegistrationRepository
     *
     * @var \RKW\RkwRegistration\Domain\Repository\RegistrationRepository
     * @inject
     */
    protected $registrationRepository;


    /**
     * ServiceRepository
     *
     * @var \RKW\RkwRegistration\Domain\Repository\ServiceRepository
     * @inject
     */
    protected $serviceRepository;


    /**
     * FacebookUserRepository
     *
     * @var \RKW\RkwRegistration\Domain\Repository\FacebookUserRepository
     * @inject
     */
    protected $facebookUserRepository;


    /**
     * TwitterUserRepository
     *
     * @var \RKW\RkwRegistration\Domain\Repository\TwitterUserRepository
     * @inject
     */
    protected $twitterUserRepository;


    /**
     * SysDomainRepository
     *
     * @var \RKW\RkwRegistration\Domain\Repository\SysDomainRepository
     * @inject
     */
    protected $sysDomainRepository;


    /**
     * Persistence Manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     * @inject
     */
    protected $persistenceManager;


    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     * @inject
     */
    protected $objectManager;


    /**
     * action welcome
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function welcomeAction()
    {

        // only for logged in users!
        $this->hasUserValidLoginRedirect();

        // check email!
        $this->hasUserValidEmailRedirect();

        // check basic mandatory fields
        $this->hasUserBasicFieldsRedirect();

        // check if there are new services where the user has fill out mandatory fields
        $services = $this->serviceRepository->findEnabledByAdminByUser($this->getFrontendUser());

        $this->view->assignMultiple(
            array(
                'services'        => $services,
                'frontendUser'    => $this->getFrontendUser(),
                'editUserPid'     => intval($this->settings['users']['editUserPid']),
                'deleteUserPid'   => intval($this->settings['users']['deleteUserPid']),
                'editPasswordPid' => intval($this->settings['users']['editPasswordPid']),
                'logoutPid'       => intval($this->settings['users']['logoutPid']),
            )
        );
    }


    /**
     * action editUser
     *
     * @return void
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function editUserAction()
    {

        $registeredUser = null;
        if (!$registeredUser = $this->getFrontendUser()) {

            $this->redirectToLogin();

            return;
            //===
        }

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        /** @var \RKW\RkwRegistration\Domain\Repository\TitleRepository $titleRepository */
        $titleRepository = $objectManager->get('RKW\\RkwRegistration\\Domain\\Repository\\TitleRepository');

        $titles = $titleRepository->findAllOfType(true, false, false);

        $this->view->assignMultiple(
            array(
                'frontendUser' => $registeredUser,
                'welcomePid'   => intval($this->settings['users']['welcomePid']),
                'titles' => $titles
            )
        );
    }

    /**
     * action update
     *
     * @param \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser
     * @validate $frontendUser \RKW\RkwRegistration\Validation\FormValidator
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function updateUserAction(\RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser)
    {

        // for logged in users only!
        $this->hasUserValidLoginRedirect();

        // all mandatory fields should be checked here.
        // therefore we can finally add the user to all relevant groups now
        $serviceClass = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\Service');
        $serviceClass->addUserToAllGrantedGroups($frontendUser);

        if ($frontendUser->getTxRkwregistrationTitle()) {
            $frontendUser->setTxRkwregistrationTitle(\RKW\RkwRegistration\Utilities\TitleUtility::extractTxRegistrationTitle($frontendUser->getTxRkwregistrationTitle()->getName()));
        }

        $this->frontendUserRepository->update($frontendUser);
        $this->addFlashMessage(
            \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                'registrationController.message.update_successfull', $this->extensionName
            )
        );



        $this->redirect('editUser');
    }

    /**
     * action deleteShow
     *
     * @return void
     */
    public function deleteUserShowAction()
    {

        // for logged in users only!
        $this->hasUserValidLoginRedirect();

        $this->view->assignMultiple(
            array(
                'welcomePid' => intval($this->settings['users']['welcomePid']),
            )
        );
    }


    /**
     * action delete
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function deleteUserAction()
    {

        $frontendUser = null;
        if (!$frontendUser = $this->getFrontendUser()) {
            $this->redirectToLogin();

            return;
            //===
        }

        /** @var \RKW\RkwRegistration\Tools\Registration $registration */
        $registration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\Registration');
        $registration->delete($frontendUser);

        $this->addFlashMessage(
            \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                'registrationController.message.delete_successfull', $this->extensionName
            )
        );

        if ($this->settings['users']['loginPid']) {
            $this->redirect('loginShow', null, null, array('noRedirect' => 1), $this->settings['users']['loginPid']);
        }

        $this->redirect('index');

        return;
        //===


    }


    /**
     * action editUser
     *
     * @return void
     */
    public function editPasswordAction()
    {

        // for logged in users only!
        $this->hasUserValidLoginRedirect();

        $this->view->assignMultiple(
            array(
                'welcomePid' => intval($this->settings['users']['welcomePid']),
            )
        );
    }


    /**
     * action update password
     *
     * @param string $passwordOld
     * @param array  $passwordNew
     * @validate $passwordNew \RKW\RkwRegistration\Validation\PasswordValidator
     * @return void
     * @throws \RKW\RkwRegistration\Exception
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function updatePasswordAction($passwordOld, $passwordNew)
    {


        // check if user is logged in
        $frontendUser = null;
        if (!$frontendUser = $this->getFrontendUser()) {
            $this->redirectToLogin();

            return;
            //===
        }

        if (!$passwordOld) {
            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'registrationController.error.password_empty', $this->extensionName
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );

            $this->redirect('editPassword');

            return;
            //===
        }

        // check if password is valid
        /** @var \RKW\RkwRegistration\Tools\Authentication $authentication */
        $authentication = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\Authentication');
        if (
            ($username = $frontendUser->getUsername())
            && ($registeredUser = $authentication->validateUser($username, $passwordOld))
            && ($registeredUser instanceof \RKW\RkwRegistration\Domain\Model\FrontendUser)
        ) {

            // set password to the given one
            Password::generatePassword($registeredUser, $passwordNew['first']);
            $this->frontendUserRepository->update($registeredUser);

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'registrationController.message.update_password', $this->extensionName
                )
            );

            // redirect
            if ($this->settings['users']['welcomePid']) {
                $this->redirect('welcome', null, null, null, $this->settings['users']['welcomePid']);
            }

            $this->redirect('welcome');

            return;
            //===

        }

        $this->addFlashMessage(
            \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                'registrationController.error.password_not_updated', $this->extensionName
            ),
            '',
            \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
        );

        $this->redirect('editPassword');

    }


    /**
     * action forgot password show
     *
     * @return void
     */
    public function passwordForgotShowAction()
    {

        // nothing to do here
    }


    /**
     * action forgot password
     *
     * @param string $username
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function passwordForgotAction($username)
    {

        if (!$username) {
            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'registrationController.error.login_no_username', $this->extensionName
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );

            $this->redirect('passwordForgotShow');

            return;
            //===
        }

        // check if user exists
        if ($registeredUser = $this->frontendUserRepository->findOneByUsername(strtolower($username))) {

            // reset password
            $plaintextPassword = Password::generatePassword($registeredUser);
            $this->frontendUserRepository->update($registeredUser);

            // dispatcher for e.g. E-Mail
            $this->signalSlotDispatcher->dispatch(__CLASS__, self::SIGNAL_AFTER_USER_PASSWORD_RESET, array($registeredUser, $plaintextPassword));

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'registrationController.message.new_password', $this->extensionName
                )
            );

            $this->redirect('loginShow', null, null, array('noRedirect' => 1));

            return;
            //===
        }

        $this->addFlashMessage(
            \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                'registrationController.error.invalid_username', $this->extensionName
            ),
            '',
            \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
        );

        $this->redirect('passwordForgotShow');
    }


    /**
     * action signUp
     * contains all login forms
     *
     * @param boolean $logoutMessage
     * @return void
     * @throws \RKW\RkwRegistration\Exception
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function loginShowAction($logoutMessage = false)
    {

        // not for already logged in users!
        if ($this->getFrontendUserId()) {

            /** @var \RKW\RkwRegistration\Tools\RedirectLogin $redirectLogin */
            $redirectLogin = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\RedirectLogin');
            if (
                ($xdlCookieDomain = $redirectLogin->getXdlDomain())
                && ($this->request->hasArgument('xdlUrl'))
                && ($xdlParamUrl = $this->request->getArgument('xdlUrl'))
                && ($xdlCookieDomain != $redirectLogin->getDomain($xdlParamUrl))
            ) {

                // show link with token to anonymous user
                $this->addFlashMessage(
                    \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                        'registrationController.message.xdl_login_hint',
                        $this->extensionName,
                        array(
                            $xdlCookieDomain,
                        )
                    )
                );
            }

            if ($this->settings['users']['welcomePid']) {
                $this->redirect('welcome', null, null, null, $this->settings['users']['welcomePid']);
            }

            $this->redirect('welcome');
            //===
        }

        // load facebook- object since this needs a special treatment
        /** @var \RKW\RkwRegistration\SocialMedia\Facebook $facebook
        $facebookLogin = null;
        try {

            if ($facebook = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\SocialMedia\\Facebook')) {

                $facebookLogin = $facebook->login();
            }

        } catch (\Exception $e) {
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, sprintf('Error using Facebook-API for Login. Message: %s', str_replace(array("\n", "\r"), '', $e->getMessage())));
        }*/


        //=============================
        // if user is logged in
        /*
        if (
            (is_object($facebookLogin))
            && ($facebookLogin instanceof \Facebook\GraphNodes\GraphUser)
        ) {

            try {

                /** @var \RKW\RkwRegistration\Tools\Authentication $authentication
                $authentication = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\Authentication');

                // load data into model
                /** @var \RKW\RkwRegistration\Domain\Model\FacebookUser $facebookUser
                $facebookUser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Domain\\Model\\FacebookUser');
                $facebookUser->insertData($facebookLogin);

                // check if user exists
                if ($registeredUser = $this->facebookUserRepository->findOneByUsernameInactive(strtolower($facebookUser->getUsername()))) {

                    // check if user is valid (not deactivated)
                    if ($authentication->validateSocialMediaUser($registeredUser)) {

                        // login user
                        $authentication->loginUser($registeredUser);

                        /** @var \RKW\RkwRegistration\Tools\RedirectLogin $redirectLogin
                        $redirectLogin = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\RedirectLogin');
                        if ($url = $redirectLogin->getRedirectUrlLogin()) {
                            $this->redirectToUri($url);
                        }

                        // redirect
                        if ($this->settings['users']['welcomePid']) {
                            $this->redirect('welcome', null, null, null, $this->settings['users']['welcomePid']);
                        }

                        $this->redirect('welcome');
                        //===

                    } else {

                        $this->addFlashMessage(
                            \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                                'registrationController.error.invalid_socialmedia_login', $this->extensionName
                            ),
                            '',
                            \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
                        );
                    }

                    // else register user!
                } else {

                    // register user
                    /** @var \RKW\RkwRegistration\Tools\Registration $registration
                    $registration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\Registration');
                    $registeredUser = $registration->register($facebookUser, true);

                    // check if user is valid
                    if ($authentication->validateSocialMediaUser($registeredUser)) {

                        // login user
                        $authentication->loginUser($registeredUser);

                        /** @var \RKW\RkwRegistration\Tools\RedirectLogin $redirectLogin
                        $redirectLogin = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\RedirectLogin');
                        if ($url = $redirectLogin->getRedirectUrlLogin()) {
                            $this->redirectToUri($url);
                        }

                        // redirect new user to login
                        if ($this->settings['users']['welcomePid']) {
                            $this->redirect('welcome', null, null, null, $this->settings['users']['welcomePid']);
                        }

                        $this->redirect('welcome');
                        //===

                    } else {

                        $this->addFlashMessage(
                            \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                                'registrationController.error.invalid_socialmedia_login', $this->extensionName
                            ),
                            '',
                            \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
                        );
                    }
                }

            } catch (\Facebook\Exceptions\FacebookSDKException $e) {

                $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, sprintf('Error using Facebook-API for Login. Message: %s', str_replace(array("\n", "\r"), '', $e->getMessage())));
                $this->addFlashMessage(
                    \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                        'registrationController.error.facebook_unexpected', $this->extensionName
                    )
                );
            }
        } else {
        */

        /** @var \RKW\RkwRegistration\Tools\RedirectLogin $redirectLogin */
        $redirectLogin = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\RedirectLogin');
        $redirectLogin->setRedirectUrl($this->request);

        if (
            ($this->controllerContext->getFlashMessageQueue()->isEmpty())
            && (!$logoutMessage)
        ) {

            // set message including link
            $registerLink = '';
            if ($this->settings['users']['registrationPid']) {

                /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
                $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

                /** @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder $uriBuilder */
                $uriBuilder = $objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder');
                $registerLink = $uriBuilder->reset()
                    ->setTargetPageUid(intval($this->settings['users']['registrationPid']))
                    ->setUseCacheHash(false)
                    ->setArguments(
                        array(
                            'tx_rkwregistration_rkwregistration' => array(
                                'controller' => 'Registration',
                                'action'     => 'registerShow',
                            ),
                        )
                    )
                    ->build();
            }

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'registrationController.message.login_message',
                    $this->extensionName,
                    array($registerLink)
                )
            );
        }


        if ($logoutMessage) {

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'registrationController.message.logout_message', $this->extensionName
                )
            );
        }

            //=============================
            // default
        /*
            $this->view->assignMultiple(
                array(
                    'facebookLogin' => $facebookLogin,
                )
            );
        }*/

    }


    /**
     * action loginExternal
     *
     * @param boolean $logoutMessage
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function loginShowExternalAction($logoutMessage = false)
    {

        // check if XDL is active
        $linkParams = array();
        $linkTargetLogin = '_blank';
        $linkTargetLogout = '_blank';

        if ($this->settings['users']['doXdl']) {

            // build url from pid!
            /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

            /** @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder $uriBuilder */
            $uriBuilder = $objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder');
            $url = $uriBuilder->reset()
                ->setTargetPageUid(intval($GLOBALS["TSFE"]->id))
                ->setCreateAbsoluteUri(true)
                ->setLinkAccessRestrictedPages(true)
                ->setUseCacheHash(false)
                ->build();

            $referrer = '';
            if ($this->settings['users']['redirectPidAfterXdlLogin']) {
                $referrer = $uriBuilder->reset()
                    ->setTargetPageUid(intval($this->settings['users']['redirectPidAfterXdlLogin']))
                    ->setCreateAbsoluteUri(true)
                    ->setLinkAccessRestrictedPages(true)
                    ->setUseCacheHash(false)
                    ->build();

                $linkTargetLogin = '_self';
            }


            if ($this->settings['users']['redirectPidAfterXdlLogout']) {
                $linkTargetLogout = '_self';
            }

            $linkParams = array(
                'tx_rkwregistration_rkwregistration' => array(
                    'xdlUrl' => $url,
                ),
            );

            if ($referrer) {
                $linkParams['tx_rkwregistration_rkwregistration']['referrer'] = $referrer;
            }
        }


        // redirect logged-in users to welcome pages
        if ($frontendUser = $this->getFrontendUserAnonymous()) {

            /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

            /** @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder $uriBuilder */
            $uriBuilder = $objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder');

            // anonymous users
            if (
                ($frontendUser->getTxRkwregistrationIsAnonymous())
                && ($this->settings['users']['anonymousRedirectPid'])
                && (intval($this->settings['users']['anonymousRedirectPid']) != intval($GLOBALS['TSFE']->id))

            ) {

                $redirectUrl = $uriBuilder->reset()
                    ->setTargetPageUid(intval($this->settings['users']['anonymousRedirectPid']))
                    ->setCreateAbsoluteUri(true)
                    ->setLinkAccessRestrictedPages(true)
                    ->setUseCacheHash(false)
                    ->build();

                $this->redirectToUri($redirectUrl);

                // normal users
            } else {
                if (
                    ($this->settings['users']['doXdl'])
                    && ($this->settings['users']['redirectPidAfterXdlLogin'])
                    && (intval($this->settings['users']['redirectPidAfterXdlLogin']) != intval($GLOBALS['TSFE']->id))
                ) {

                    $redirectUrl = $uriBuilder->reset()
                        ->setTargetPageUid(intval($this->settings['users']['redirectPidAfterXdlLogin']))
                        ->setCreateAbsoluteUri(true)
                        ->setLinkAccessRestrictedPages(true)
                        ->setUseCacheHash(false)
                        ->build();

                    $this->redirectToUri($redirectUrl);
                }
            }
        }

        // show logout message
        if ($logoutMessage) {
            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'registrationController.message.logout_message', $this->extensionName
                )
            );

            // show welcome message for normal FE-Users
        } else {
            if ($frontendUser = $this->getFrontendUser()) {

                $this->addFlashMessage(
                    \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                        'registrationController.message.xdl_login_welcome',
                        $this->extensionName,
                        array($frontendUser->getUsername())
                    )
                );

            }
        }

        $this->view->assignMultiple(
            array(
                'linkParams'       => $linkParams,
                'linkTargetLogin'  => $linkTargetLogin,
                'linkTargetLogout' => $linkTargetLogout,
                'frontendUser'     => $this->getFrontendUserAnonymous(),
                //'myRkwSysDomain'   => $this->sysDomainRepository->findByUid(intval($this->settings['users']['myRkwSysDomain']))
            )
        );
    }


    /**
     * action loginAnonymous
     *
     * @return void
     * @throws \RKW\RkwRegistration\Exception
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    public function loginAnonymousAction()
    {

        if (!$this->getFrontendUser()) {

            /** @var \RKW\RkwRegistration\Tools\Authentication $authentication */
            $authentication = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\Authentication');

            // check for token
            if (
                ($this->request->hasArgument('token'))
                && ($token = $this->request->getArgument('token'))
            ) {

                // find anonymous user by token and login
                if ($anonymousUser = $authentication->validateAnonymousUser($token)) {
                    $authentication::loginUser($anonymousUser);

                    // redirect user
                    if ($this->settings['users']['anonymousRedirectPid']) {

                        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
                        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

                        /** @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder $uriBuilder */
                        $uriBuilder = $objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder');

                        $redirectUrl = $uriBuilder->reset()
                            ->setTargetPageUid(intval($this->settings['users']['anonymousRedirectPid']))
                            ->setCreateAbsoluteUri(true)
                            ->setLinkAccessRestrictedPages(true)
                            ->setUseCacheHash(false)
                            ->build();

                        $this->redirectToUri($redirectUrl);

                    } else {
                        $this->redirect('index');
                    }

                } else {

                    $authentication::logoutUser();

                    $this->addFlashMessage(
                        \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                            'registrationController.error.invalid_anonymous_token', $this->extensionName
                        ),
                        '',
                        \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
                    );
                }

            } else {

                /** @var \RKW\RkwRegistration\Tools\Registration $registration */
                $registration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\Registration');

                // register anonymous user and login
                $anonymousUser = $registration->registerAnonymous();
                $authentication::loginUser($anonymousUser);

                $this->redirect('loginHintAnonymous');
            }

        } else {

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'registrationController.error.anonymous_login_impossible', $this->extensionName
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );

        }

        $this->redirect('loginShowExternal');
    }

    /**
     * action loginRedirectAnonymous
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */

    public function loginHintAnonymousAction()
    {

        if (!$this->getFrontendUserAnonymous()) {

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'registrationController.error.anonymous_login_impossible', $this->extensionName
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );

            $this->redirect('loginShowExternal');
        }

        // generate link for copy&paste
        $url = $this->uriBuilder->reset()
            ->setUseCacheHash(false)
            ->setArguments(
                array('tx_rkwregistration_rkwregistration' =>
                    array(
                      'controller' => 'Registration',
                      'action'     => 'loginAnonymous',
                      'token'      => $this->getFrontendUserAnonymous()->getUsername(),
                    ),
                )
            )
            ->setCreateAbsoluteUri(true)
            ->build();

        // show link with token to anonymous user
        $this->addFlashMessage(
            \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                'registrationController.message.anonymous_link',
                $this->extensionName,
                array(
                    intval(intval($this->settings['users']['lifetimeAnonymous']) / 60 / 60 / 24),
                    $url,
                )
            )
        );

    }

    /**
     * action twitter
     *
     * @return void
     * @throws \RKW\RkwRegistration\Exception
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function loginTwitterAction()
    {

        // response from twitter after the user has logged on to twitter
        $oauthVerifier = preg_replace('/[^a-zA-Z0-9]/', '', \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('oauth_verifier'));
        $oauthToken = preg_replace('/[^a-zA-Z0-9]/', '', \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('oauth_token'));

        // try to connect to twitter
        try {

            $twitter = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\SocialMedia\\Twitter');
            //=============================
            // check if we start with the login redirect
            if (
                (!$oauthVerifier)
                && (!$oauthToken)
            ) {
                $twitter->login();

                //=============================
                // check if user comes from twitter and register him (if needed)
            } elseif (
                ($oauthVerifier)
                && ($oauthToken)
            ) {

                // load user data from API
                $userData = $twitter->getUserData($oauthVerifier, $oauthToken);

                /** @var \RKW\RkwRegistration\Tools\Authentication $authentication */
                $authentication = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\Authentication');

                /** @var \RKW\RkwRegistration\Domain\Model\TwitterUser $twitterUser */
                // load twitter-model and insert data
                $twitterUser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Domain\\Model\\TwitterUser');
                $twitterUser->insertData($userData);

                // if he is already registered we simply start the session and redirect him
                if ($registeredUser = $this->twitterUserRepository->findOneByUsernameInactive(strtolower($twitterUser->getUsername()))) {

                    // check if user is valid (and not deactivated)
                    if ($authentication->validateSocialMediaUser($registeredUser)) {

                        // login user
                        $authentication->loginUser($registeredUser);

                        /** @var \RKW\RkwRegistration\Tools\RedirectLogin $redirectLogin */
                        $redirectLogin = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\RedirectLogin');
                        if ($url = $redirectLogin->getRedirectUrlLogin()) {
                            $this->redirectToUri($url);
                        }

                        // redirect
                        if ($this->settings['users']['welcomePid']) {
                            $this->redirect('welcome', null, null, null, $this->settings['users']['welcomePid']);
                        }

                        $this->redirect('welcome');
                        //===

                    } else {

                        $this->addFlashMessage(
                            \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                                'registrationController.error.invalid_socialmedia_login', $this->extensionName
                            ),
                            '',
                            \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
                        );
                    }

                    // else register user!
                } else {

                    // register user
                    /** @var \RKW\RkwRegistration\Tools\Registration $registration */
                    $registration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\Registration');
                    $registeredUser = $registration->register($twitterUser, true);

                    // check if user is valid (and not deactivated)
                    if ($authentication->validateSocialMediaUser($registeredUser)) {

                        // login user
                        $authentication->loginUser($registeredUser);

                        /** @var \RKW\RkwRegistration\Tools\RedirectLogin $redirectLogin */
                        $redirectLogin = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\RedirectLogin');
                        if ($url = $redirectLogin->getRedirectUrlLogin()) {
                            $this->redirectToUri($url);
                        }

                        // redirect new user to login
                        if ($this->settings['users']['welcomePid']) {
                            $this->redirect('welcome', null, null, null, $this->settings['users']['welcomePid']);
                        }

                        $this->redirect('welcome');
                        //===

                    } else {

                        $this->addFlashMessage(
                            \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                                'registrationController.error.invalid_socialmedia_login', $this->extensionName
                            ),
                            '',
                            \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
                        );
                    }
                }
            }

        } catch (\RKW\RkwRegistration\Exception $e) {

            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, sprintf('Error using Twitter-API for Login. Message: %s', str_replace(array("\n", "\r"), '', $e->getMessage())));
            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'registrationController.error.twitter_unexpected', $this->extensionName
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );
        }

        // redirect
        if ($this->settings['users']['loginPid']) {
            $this->redirect('loginShow', null, null, array('noRedirect' => 1), $this->settings['users']['loginPid']);
        }

        $this->redirect('loginShow', null, null, array('noRedirect' => 1));
    }


    /**
     * action login
     *
     * @param string $username
     * @param string $password
     * @return void
     * @throws \RKW\RkwRegistration\Exception
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \RKW\RkwRegistration\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function loginAction($username, $password)
    {


        if (!$username) {
            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'registrationController.error.login_no_username', $this->extensionName
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );

            $this->redirect('loginShow');
            //===
        }

        if (!$password) {
            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'registrationController.error.login_no_password', $this->extensionName
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );

            $this->redirect('loginShow');
            //===
        }


        // check if there is a user that matches and log him in
        /** @var \RKW\RkwRegistration\Tools\Authentication $authenticate */
        $authenticate = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\Authentication');
        $validateResult = null;
        if (
            ($validateResult = $authenticate->validateUser(strtolower($username), $password))
            && ($validateResult instanceof \RKW\RkwRegistration\Domain\Model\FrontendUser)
        ) {

            $authenticate->loginUser($validateResult);

            /** @var \RKW\RkwRegistration\Tools\RedirectLogin $redirectLogin */
            $redirectLogin = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\RedirectLogin');
            if ($url = $redirectLogin->getRedirectUrlLogin()) {
                $this->redirectToUri($url);
            }

            if ($this->settings['users']['welcomePid']) {
                $this->redirect('welcome', null, null, null, $this->settings['users']['welcomePid']);
            }

            $this->redirect('welcome');
            //===
        }

        // user blocked
        if ($validateResult == 2) {

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'registrationController.error.login_blocked', $this->extensionName,
                    array(($this->settings['users']['maxLoginErrors'] ? intval($this->settings['users']['maxLoginErrors']) : 10))
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );

            // wrong login
        } else {
            if ($validateResult == 1) {

                $this->addFlashMessage(
                    \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                        'registrationController.error.wrong_login', $this->extensionName
                    ),
                    '',
                    \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
                );

                // user not found
            } else {

                $this->addFlashMessage(
                    \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                        'registrationController.error.user_not_found', $this->extensionName
                    ),
                    '',
                    \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
                );
            }
        }

        $this->redirect('loginShow');

    }


    /**
     * action login for cross domain redirect
     *
     * @param string $xdlToken
     * @param string $xdlRedirect
     * @return void
     * @throws \RKW\RkwRegistration\Exception
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \RKW\RkwRegistration\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function xdlLoginAction($xdlToken = '', $xdlRedirect = '')
    {
        // if cross domain login is set, we try to login here
        $error = true;
        if ($xdlToken) {

            // 1. validate user by temporary token
            /** @var \RKW\RkwRegistration\Tools\Authentication $authentication */
            $authentication = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\Authentication');
            if ($frontendUser = $authentication->validateCrossDomainLoginToken($xdlToken)) {
                $authentication->loginUser($frontendUser);
                $error = false;

                // 2. if there is a valid redirect, go for it!
                if ($xdlRedirect) {

                    /** @var \RKW\RkwRegistration\Tools\RedirectLogin $redirectLogin */
                    $redirectLogin = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\RedirectLogin');
                    if ($url = $redirectLogin->checkRedirectUrl($xdlRedirect)) {
                        $version = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version);
                        if ($version >=  8000000) {
                            // Fix for TYPO3 8.7: Without setting this cookie the user would have to reload the browser manually
                            setcookie('fe_typo_user', $GLOBALS['TSFE']->fe_user->id, null, "/");
                        }
                        $this->redirectToUri($url);
                    } else {
                        $error = true;
                    }
                }
            }
        }

        if ($error) {
            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'registrationController.error.xdl_login_error', $this->extensionName
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );
        }


        $this->redirect('loginShowExternal');

    }


    /**
     * action logout
     *
     * @param string $xdlRedirect
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function xdlLogoutAction($xdlRedirect = '')
    {

        // 1. do logout here!
        Authentication::logoutUser();

        // 2. check for redirect page in plugin and override target page if set
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

        /** @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder $uriBuilder */
        $uriBuilder = $objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder');
        if ($this->settings['users']['redirectPidAfterXdlLogout']) {

            // do not redirect to yourself!
            if (intval($this->settings['users']['redirectPidAfterXdlLogout']) == intval($GLOBALS['TSFE']->id)) {
                $xdlRedirect = 0;
            } else {
                $xdlRedirect = $uriBuilder->reset()
                    ->setTargetPageUid(intval($this->settings['users']['redirectPidAfterXdlLogout']))
                    ->setCreateAbsoluteUri(true)
                    ->setLinkAccessRestrictedPages(true)
                    ->setUseCacheHash(false)
                    ->build();
            }

        }

        // 3. do redirect after logout
        $error = false;
        if ($xdlRedirect) {

            /** @var \RKW\RkwRegistration\Tools\RedirectLogin $redirectLogin */
            $redirectLogin = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\RedirectLogin');
            if ($url = $redirectLogin->checkRedirectUrl($xdlRedirect)) {
                $this->redirectToUri($url);
            } else {
                $error = true;
            }
        }

        if ($error) {
            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'registrationController.error.xdl_logout_error', $this->extensionName
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );

            $this->redirect('loginShowExternal');
        }

        $this->redirect('loginShowExternal', null, null, array('logoutMessage' => 1));

    }


    /**
     * action logout
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function logoutAction()
    {
        // 1. do logout here
        Authentication::logoutUser();

        // 2. check for XDL-logout in cookie- then we log out on foreign page, too!
        /** @var \RKW\RkwRegistration\Tools\RedirectLogin $redirectLogin */
        $redirectLogin = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\RedirectLogin');
        if ($url = $redirectLogin->getRedirectUrlLogout()) {
            $this->redirectToUri($url);
        }

        // 3. redirect to login page including message
        if ($this->settings['users']['loginPid']) {
            $this->redirect('loginShow', null, null, array('logoutMessage' => 1), $this->settings['users']['loginPid']);
        }

        $this->redirect('index');

    }


    /**
     * action logoutAnonymous
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function logoutExternalAction()
    {

        // redirect logged-in users to welcome pages
        if ($frontendUser = $this->getFrontendUserAnonymous()) {

            // simply logout anonymous users and show hint
            if ($frontendUser->getTxRkwregistrationIsAnonymous()) {

                Authentication::logoutUser();

                // redirect to login page including message
                if ($this->settings['users']['loginExternalPid']) {
                    $this->redirect('loginShowExternal', null, null, array('logoutMessage' => 1), $this->settings['users']['loginExternalPid']);
                }

                if ($this->settings['users']['loginPid']) {
                    $this->redirect('loginShow', null, null, array('logoutMessage' => 1), $this->settings['users']['loginPid']);
                }


                $this->redirect('index');

                // redirect normal users to default logout page
            } else {
                $this->redirect('logout', null, null, null, $this->settings['users']['logoutPid']);
            }
        }
    }

    /**
     * Takes optin parameters and checks them
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function optInAction()
    {

        $tokenYes = preg_replace('/[^a-zA-Z0-9]/', '', ($this->request->hasArgument('token_yes') ? $this->request->getArgument('token_yes') : ''));
        $tokenNo = preg_replace('/[^a-zA-Z0-9]/', '', ($this->request->hasArgument('token_no') ? $this->request->getArgument('token_no') : ''));
        $userSha1 = preg_replace('/[^a-zA-Z0-9]/', '', $this->request->getArgument('user'));

        /** @var \RKW\RkwRegistration\Tools\Registration $register */
        $register = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\Registration');
        $check = $register->checkTokens($tokenYes, $tokenNo, $userSha1, $this->request);

        if ($check == 1) {

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'registrationController.message.registration_successfull', $this->extensionName
                )
            );

            if ($this->settings['users']['loginPid']) {
                $this->redirect('loginShow', null, null, array('noRedirect' => 1), $this->settings['users']['loginPid']);
            }


        } elseif ($check == 2) {

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'registrationController.message.registration_canceled', $this->extensionName
                )
            );


        } else {

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'registrationController.error.registration_error', $this->extensionName
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );
        }


        $this->redirect('registerShow');
        //====

    }


    /**
     * action register
     * RKW own register action
     *
     * @param \RKW\RkwRegistration\Domain\Model\FrontendUser $newFrontendUser
     * @ignorevalidation $newFrontendUser
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function registerShowAction(\RKW\RkwRegistration\Domain\Model\FrontendUser $newFrontendUser = null)
    {
        // not for already logged in users!
        if ($this->getFrontendUserId()) {

            if ($this->settings['users']['welcomePid']) {
                $this->redirect('welcome', null, null, null, $this->settings['users']['welcomePid']);
            }

            $this->redirect('welcome');
            //===
        }

        /** @var \RKW\RkwRegistration\Tools\RedirectLogin $redirectLogin */
        $redirectLogin = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\RedirectLogin');
        $redirectLogin->setRedirectUrl($this->request);

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        /** @var \RKW\RkwRegistration\Domain\Repository\TitleRepository $titleRepository */
        $titleRepository = $objectManager->get('RKW\\RkwRegistration\\Domain\\Repository\\TitleRepository');

        $titles = $titleRepository->findAllOfType(true, false, false);

        $this->view->assignMultiple(
            array(
                'newFrontendUser' => $newFrontendUser,
                'termsPid'        => intval($this->settings['users']['termsPid']),
                'titles' => $titles
            )
        );
    }


    /**
     * action create
     * created a rkw user
     *
     * @param \RKW\RkwRegistration\Domain\Model\FrontendUser $newFrontendUser
     * @param integer                                        $terms
     * @param integer                                        $privacy
     * @validate $newFrontendUser \RKW\RkwRegistration\Validation\FormValidator
     * @return void
     * @throws \RKW\RkwRegistration\Exception
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function registerAction(\RKW\RkwRegistration\Domain\Model\FrontendUser $newFrontendUser, $terms, $privacy)
    {
        if (!$terms) {
            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'registrationController.error.accept_terms', $this->extensionName
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );
            $this->forward('registerShow', null, null, array('newFrontendUser' => $newFrontendUser));
            //===
        }

        if (!$privacy) {
            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'registrationController.error.accept_privacy', $this->extensionName
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );
            $this->forward('registerShow', null, null, array('newFrontendUser' => $newFrontendUser));
            //===
        }

        if ($this->frontendUserRepository->findOneByUsernameInactive($newFrontendUser->getEmail())) {

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'registrationController.error.username_exists', $this->extensionName
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );

            $this->forward('registerShow', null, null, array('newFrontendUser' => $newFrontendUser));
            //===
        }

        // register new user
        /** @var \RKW\RkwRegistration\Tools\Registration $registration */
        $registration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\Registration');
        $registration->register($newFrontendUser, false, null, null, $this->request);

        $this->addFlashMessage(
            \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                'registrationController.message.registration_watch_for_email', $this->extensionName
            )
        );

        if ($this->settings['users']['loginPid']) {
            $this->redirect('loginShow', null, null, array('noRedirect' => 1), $this->settings['users']['loginPid']);
        }

        $this->redirect('registerShow');
    }



    /**
     * action goBack
     * sends a user back to the domain he comes from
     *
     * @return void
     */
    public function goBackAction()
    {
        if ($GLOBALS['TSFE']->fe_user->getKey('ses', 'rkw_registration_redirect_xdl_url')) {
            $this->redirectToUri($GLOBALS['TSFE']->fe_user->getKey('ses', 'rkw_registration_redirect_xdl_url'));
        } else {
            // set just some link, if there is no redirect url (e.g. if someone comes to mein.rkw.de directly)
            if ($this->settings['users']['welcomePid']) {
                $this->redirect('welcome', null, null, null, $this->settings['users']['welcomePid']);
            }
        }
    }

}