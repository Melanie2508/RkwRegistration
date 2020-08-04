<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function($extKey)
    {

        //=================================================================
        // Configure Plugin
        //=================================================================
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'RKW.' . $extKey,
            'Rkwregistration',
            array(
                'Registration' => 'index, welcome, welcomeMessage, xdlLoginShow, loginShow, loginShowExternal, loginTwitter, login, xdlLogin, xdlLogout, logout, logoutExternal, loginAnonymous, loginHintAnonymous, registerShow, register, optin, passwordForgot, passwordForgotShow, createTwitterUser, editUser, updateUser, deleteUserShow, deleteUser, editPassword, updatePassword',
                'Service' => 'list, show, create, delete, optIn',
            ),
            // non-cacheable actions
            array(
                'Registration' => 'index, welcome, welcomeMessage, xdlLoginShow, loginShow, loginShowExternal, loginTwitter, login, xdlLogin, xdlLogout, logout, logoutExternal, loginAnonymous, loginHintAnonymous, registerShow, register, optin, passwordForgot, passwordForgotShow, createTwitterUser, editUser, updateUser, deleteUserShow, deleteUser, editPassword, updatePassword',
                'Service' => 'list, show, create, delete, optIn',
            )
        );


        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'RKW.' . $extKey,
            'RkwregistrationAjax',
            array(
                'Ajax' => 'loginInfoInit, loginInfo'
            ),
            // non-cacheable actions
            array(
                'Ajax' => 'loginInfo'
            )
        );

        //=================================================================
        // Register CommandController
        //=================================================================
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'RKW\\RkwRegistration\\Controller\\CleanupCommandController';

        //=================================================================
        // Register Hook for Backend-Delete
        //=================================================================
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][$extKey] = 'RKW\\RkwRegistration\\Hooks\\DatahandlerHook';


        //=================================================================
        // Register Signal-Slots
        //=================================================================
        /**
         * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
         */
        $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
        $signalSlotDispatcher->connect(
            'RKW\\RkwRegistration\\Tools\\Registration',
            \RKW\RkwRegistration\Tools\Registration::SIGNAL_AFTER_CREATING_OPTIN_USER,
            'RKW\\RkwRegistration\\Service\\RkwMailService',
            'handleCreateUserEvent'
        );

        $signalSlotDispatcher->connect(
            'RKW\\RkwRegistration\\Tools\\Registration',
            \RKW\RkwRegistration\Tools\Registration::SIGNAL_AFTER_CREATING_FINAL_USER,
            'RKW\\RkwRegistration\\Service\\RkwMailService',
            'handleRegisterUserEvent'
        );

        $signalSlotDispatcher->connect(
            'RKW\\RkwRegistration\\Tools\\Registration',
            \RKW\RkwRegistration\Tools\Registration::SIGNAL_AFTER_USER_REGISTER_GRANT,
            'RKW\\RkwRegistration\\Service\\RkwMailService',
            'handleRegisterUserEvent'
        );

        $signalSlotDispatcher->connect(
            'RKW\\RkwRegistration\\Controller\\RegistrationController',
            \RKW\RkwRegistration\Controller\RegistrationController::SIGNAL_AFTER_USER_PASSWORD_RESET,
            'RKW\\RkwRegistration\\Service\\RkwMailService',
            'handlePasswordResetEvent'
        );

        $signalSlotDispatcher->connect(
            'RKW\\RkwRegistration\\Controller\\ServiceController',
            \RKW\RkwRegistration\Controller\ServiceController::SIGNAL_ADMIN_SERVICE_REQUEST,
            'RKW\\RkwRegistration\\Service\\RkwMailService',
            'handleAdminServiceEvent'
        );

        $signalSlotDispatcher->connect(
            'RKW\\RkwRegistration\\Tools\\Service',
            \RKW\RkwRegistration\Tools\Service::SIGNAL_AFTER_ADMIN_SERVICE_GRANT,
            'RKW\\RkwRegistration\\Service\\RkwMailService',
            'handleAdminServiceGrantEvent'
        );

        $signalSlotDispatcher->connect(
            'RKW\\RkwRegistration\\Tools\\Service',
            \RKW\RkwRegistration\Tools\Service::SIGNAL_AFTER_ADMIN_SERVICE_DENIAL,
            'RKW\\RkwRegistration\\Service\\RkwMailService',
            'handleAdminServiceDenialEvent'
        );

        //=================================================================
        // Register Logger
        //=================================================================
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['RKW']['RkwRegistration']['writerConfiguration'] = array(

            // configuration for WARNING severity, including all
            // levels with higher severity (ERROR, CRITICAL, EMERGENCY)
            \TYPO3\CMS\Core\Log\LogLevel::INFO => array(
                // add a FileWriter
                'TYPO3\\CMS\\Core\\Log\\Writer\\FileWriter' => array(
                    // configuration for the writer
                    'logFile' => 'typo3temp/var/logs/tx_rkwregistration.log'
                )
            ),
        );


        //=================================================================
        // AuthService
        //=================================================================
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
            $extKey,
            'auth',
            RKW\RkwRegistration\Service\FrontendUserAuthService::class,
            array(
                'title' => 'Authentication Service for fe_users',
                'description' => 'Authentication Service for fe_users',
                //'subtype' => 'authUserFE,getGroupsFE,getUserFE,processLoginDataFE',
                'subtype' => 'authUserFE, getUserFE',
                'available' => true,
                'priority' => 80,
                'quality' => 80,
                'os' => '',
                'exec' => '',
                'className' => RKW\RkwRegistration\Service\FrontendUserAuthService::class
            )
        );

        $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_alwaysFetchUser'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_alwaysAuthUser'] = true;


    },
    $_EXTKEY
);
