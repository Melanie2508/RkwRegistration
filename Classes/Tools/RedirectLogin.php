<?php

namespace RKW\RkwRegistration\Tools;

use \RKW\RkwBasics\Helper\Common;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
 * Class RedirectLogin
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwRegistration
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RedirectLogin implements \TYPO3\CMS\Core\SingletonInterface
{


    /**
     * Setting
     *
     * @var array
     */
    protected $settings;


    /**
     * Persistence Manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    protected $persistenceManager;


    /**
     * FrontendUserRepository
     *
     * @var \RKW\RkwRegistration\Domain\Repository\FrontendUserRepository
     */
    protected $frontendUserRepository;

    /**
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger;


    /**
     * Sets the redirect target (if valid)
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Request $request
     * @param string $xdlUrl
     * @param string $referrer
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    public function setRedirectUrl(\TYPO3\CMS\Extbase\Mvc\Request $request, $xdlUrl = null, $referrer = null)
    {

        // check if redirect is disabled!
        if (
            (!$request->hasArgument('noRedirect'))
            || ($request->getArgument('noRedirect') != 1)
        ) {

            // check referrer - we set it into the cookie
            /**  @var $feAuth \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication */
            if (
            ($feAuth = $GLOBALS['TSFE']->fe_user)
            ) {

                // reset cookie data
                // I don't think we need this here, because it is only triggered when the user logs in
                // if the user comes back he is ether still logged in - or has been logged out, because the cookie
                // is invalid
                // $feAuth->setKey('ses', 'rkw_registration_redirect_referrer', NULL);
                // $feAuth->setKey('ses', 'rkw_registration_redirect_xdl_url', NULL);

                // 1. Check for referrer
                if (
                    (
                        (
                            ($request->hasArgument('referrer'))
                            && ($referrer = $request->getArgument('referrer'))
                        )
                        || ($referrer)
                    )
                    && ($checkedUrl = $this->checkRedirectUrl($referrer))
                ) {
                    $feAuth->setKey('ses', 'rkw_registration_redirect_referrer', $checkedUrl);
                    // add also to rkw cookie
                    CookieService::setKey('rkw_registration_redirect_referrer', $checkedUrl);
                    $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::DEBUG, sprintf('Referrer redirect set to %s.', $checkedUrl));
                }

                // 2. Check for XDL url
                if (
                    (
                        (
                            ($request->hasArgument('xdlUrl'))
                            && ($xdlUrl = $request->getArgument('xdlUrl'))
                        )
                        || ($xdlUrl)
                    )
                    && ($checkedUrl = $this->checkRedirectUrl($xdlUrl))
                ) {
                    $feAuth->setKey('ses', 'rkw_registration_redirect_xdl_url', $checkedUrl);
                    // add also to rkw cookie
                    CookieService::setKey('rkw_registration_redirect_xdl_url', $checkedUrl);
                    $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::DEBUG, sprintf('XDL redirect set to %s.', $checkedUrl));
                }
            }
        }
    }

    /**
     * Gets the redirect target for the login
     *
     * @return string | NULL
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function getRedirectUrlLogin()
    {

        $settings = $this->getSettings();
        $url = null;

        // check referrer and return it, if valid
        /**  @var $feAuth \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication */
        if (
        ($feAuth = $GLOBALS['TSFE']->fe_user)
        ) {

            // 1. Check for XDL-Redirect url, because this would be a step before the redirect to the referrer
            if (
                ($xdlUrl = $feAuth->getKey('ses', 'rkw_registration_redirect_xdl_url'))
                && ($baseUrl = $this->checkRedirectUrl($xdlUrl))
            ) {

                // 1.1 set default params with action and controller
                $params = array(
                    'tx_rkwregistration_rkwregistration[controller]=Registration',
                    'tx_rkwregistration_rkwregistration[action]=xdlLogin',
                );

                // 1.2 Generate token
                /** @var \RKW\RkwRegistration\Tools\Authentication $authentication */
                $authentication = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwRegistration\\Tools\\Authentication');
                $params[] = 'tx_rkwregistration_rkwregistration[xdlToken]=' . $authentication->setCrossDomainLoginToken($baseUrl);

                // 1.3 Check if there is a referrer given. If so we take this as further redirect param.
                $redirectUrl = '';
                if (
                    ($referrerUrl = $feAuth->getKey('ses', 'rkw_registration_redirect_referrer'))
                    && ($checkedRedirectUrl = $this->checkRedirectUrl($referrerUrl))
                ) {
                    $params[] = 'tx_rkwregistration_rkwregistration[xdlRedirect]=' . urlencode($checkedRedirectUrl);

                    // if no referrer is given, we take the welcome page
                } else {
                    if ($settings['users']['welcomePid']) {

                        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
                        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

                        /** @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder $uriBuilder */
                        $uriBuilder = $objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder');
                        $params[] = 'tx_rkwregistration_rkwregistration[xdlRedirect]=' .
                            urlencode(
                                $uriBuilder->reset()
                                    ->setTargetPageUid(intval($settings['users']['welcomePid']))
                                    ->setCreateAbsoluteUri(true)
                                    ->setUseCacheHash(false)
                                    ->build()
                            );
                    }
                }

                // 1.4 Add params to baseUrl
                $query = parse_url($baseUrl, PHP_URL_QUERY);
                if ($query) {
                    $url = $baseUrl . '&' . implode('&', $params);
                } else {
                    $url = $baseUrl . '?' . implode('&', $params);
                }

                $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Returned XDL url "%s" for login.', $url));


                // 2. If there is no XDL-Redirect, we only do a redirect based on the given referrer
            } else {
                if (
                    ($referrerUrl = $feAuth->getKey('ses', 'rkw_registration_redirect_referrer'))
                    && ($checkedRedirectUrl = $this->checkRedirectUrl($referrerUrl))
                ) {
                    $url = $checkedRedirectUrl;
                    $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Returned normal url "%s" for login.', $url));

                }
            }

            // reset referrer in cookie. Everything else is kept - except for logout!
            $feAuth->setKey('ses', 'rkw_registration_redirect_referrer', null);
            // remove it also from RkwCookie
            CookieService::removeKey('rkw_registration_redirect_referrer');

        }
        return $url;
        //===

    }

    /**
     * Gets the redirect target for the
     *
     * @return string | NULL
     */
    public function getRedirectUrlLogout()
    {

        $settings = $this->getSettings();
        $url = null;

        // check referrer and return it, if valid
        /**  @var $feAuth \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication */
        if (
        ($feAuth = $GLOBALS['TSFE']->fe_user)
        ) {

            // Check for XDL-Redirect url
            if (
                ($xdlUrl = $feAuth->getKey('ses', 'rkw_registration_redirect_xdl_url'))
                && ($baseUrl = $this->checkRedirectUrl($xdlUrl))
            ) {

                // 1.1 set default params with action and controller
                $params = array(
                    'tx_rkwregistration_rkwregistration[controller]=Registration',
                    'tx_rkwregistration_rkwregistration[action]=xdlLogout',
                );

                // 1.2 Generate back link
                $redirectUrl = '';
                if ($settings['users']['loginPid']) {

                    /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
                    $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

                    /** @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder $uriBuilder */
                    $uriBuilder = $objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder');
                    $params[] = 'tx_rkwregistration_rkwregistration[xdlRedirect]=' .
                        urlencode(
                            $uriBuilder->reset()
                                ->setTargetPageUid(intval($settings['users']['loginPid']))
                                ->setCreateAbsoluteUri(true)
                                ->setUseCacheHash(false)
                                ->setArguments(
                                    array(
                                        'tx_rkwregistration_rkwregistration' => array(
                                            'logoutMessage' => 1,
                                        ),
                                    )
                                )
                                ->build()
                        );
                }

                // 1.3 Add params to baseUrl
                $query = parse_url($baseUrl, PHP_URL_QUERY);
                if ($query) {
                    $url = $baseUrl . '&' . implode('&', $params);
                } else {
                    $url = $baseUrl . '?' . implode('&', $params);
                }

                $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Returned XDL url "%s" for logout.', $url));
            }

            // reset everything in cookie
            $feAuth->setKey('ses', 'rkw_registration_redirect_referrer', null);
            $feAuth->setKey('ses', 'rkw_registration_redirect_xdl_url', null);

            // remove it also from RkwCookie
            CookieService::removeKey('rkw_registration_redirect_referrer');
            CookieService::removeKey('rkw_registration_redirect_xdl_url');
        }

        return $url;
        //===
    }


    /**
     * Checks if given url is valid for redirect
     *
     * @param string|integer $url
     * @return string
     * @see \TYPO3\CMS\Felogin\Controller\FrontendLoginController
     */
    public function checkRedirectUrl($url)
    {

        $settings = $this->getSettings();
        $checkedUrl = '';

        // if it is numeric, we have a pid and have to generate the real url
        if (is_numeric($url)) {

            // build url from pid!
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
            $uriBuilder = $objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder');
            if ($uriBuilder instanceof \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder) {
                $url = $uriBuilder->reset()
                    ->setTargetPageUid(intval($url))
                    ->setCreateAbsoluteUri(true)
                    ->setLinkAccessRestrictedPages(true)
                    ->build();
            }
        }


        // check if settings are available and redirect not disabled
        $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::DEBUG, sprintf('Configured redirect domains: "%s"', $settings['redirectDomains']));
        if ($redirectDomains = $settings['redirectDomains']) {

            // Is referring url allowed to redirect?
            if ($redirectDomain = $this->getDomain($url)) {

                $validDomains = GeneralUtility::trimExplode(',', $redirectDomains, true);
                if (in_array($redirectDomain, $validDomains)) {
                    $checkedUrl = $url;
                }
            }
        }

        // take domain if we are not in production environement
        if (getenv('TYPO3_CONTEXT') != 'Production') {
            $checkedUrl = $url;
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, sprintf('URL passed as valid due to TYPO3_CONTEXT settings.', $url));
        }


        if ($checkedUrl) {
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('URL "%s" is valid for redirecting.', $checkedUrl));
        } else {
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, sprintf('URL "%s" is not valid for redirecting.', $url));
        }

        // Avoid forced logout for fe-login-extension, when trying to login immediately after a logout
        $checkedUrl = preg_replace('/[&?]logintype=[a-z]+/', '', $checkedUrl);

        return $checkedUrl;
        //===
    }


    /**
     * Gets the XDL domain
     *
     * @return string | NULL
     */
    public function getXdlDomain()
    {

        $settings = $this->getSettings();
        $url = null;

        // check referrer and return it, if valid
        /**  @var $feAuth \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication */
        if (
        ($feAuth = $GLOBALS['TSFE']->fe_user)
        ) {

            // check for XDl-Domain
            if (
                ($xdlUrl = $feAuth->getKey('ses', 'rkw_registration_redirect_xdl_url'))
                && ($checkedUrl = $this->checkRedirectUrl($xdlUrl))
                && ($domain = $this->getDomain($checkedUrl))
            ) {

                return $domain;
                //===
            }
        }

        return null;
        //===

    }


    /**
     * Extracts the domain from the given url
     *
     * @param string $url
     * @return string | NULL
     */
    public function getDomain($url)
    {

        $match = array();
        if (preg_match('#^http(s)?://([[:alnum:]._-]+)/#', $url, $match)) {
            return $match[2];
            //===
        }

        return null;
        //===

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

        //===

        return $this->settings;
        //===
    }


    /**
     * Returns logger instance
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger()
    {

        if (!$this->logger instanceof \TYPO3\CMS\Core\Log\Logger) {
            $this->logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger(__CLASS__);
        }

        return $this->logger;
        //===
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
        //===
    }


    /**
     * Returns PersistanceManager
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
        //===
    }
}