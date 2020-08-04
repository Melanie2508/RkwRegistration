<?php

namespace RKW\RkwRegistration\Service;

use \RKW\RkwBasics\Helper\Common;
use \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;

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
 * FrontendUserAuthService
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwRegistration
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FrontendUserAuthService extends \TYPO3\CMS\Sv\AuthenticationService
{
    /**
     * 200 - authenticated and no more checking needed
     */
    const STATUS_AUTHENTICATION_SUCCESS_BREAK = 200;

    /**
     * 0 - this service was the right one to authenticate the user but it failed
     */
    const STATUS_AUTHENTICATION_FAILURE_BREAK = 0;

    /**
     * 100 - just go on. User is not authenticated but there's still no reason to stop
     */
    const STATUS_AUTHENTICATION_FAILURE_CONTINUE = 100;



    function init() {
        $available = parent::init();
        return $available;
    }



    /**
     * Use it by adding "getUserFE" to service subtype in ext_localconf
     *
     * Find a user (eg. look up the user record in database when a login is sent)
     *
     * @return mixed User array or FALSE
     */
    public function getUser()
    {

        if ($this->login['status'] !== 'login') {
            return false;
        }

        /*
        if ((string)$this->login['uident_text'] === '') {
            // Failed Login attempt (no password given)
            $this->writelog(255, 3, 3, 2, 'Login-attempt from %s (%s) for username \'%s\' with an empty password!', [
                $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']
            ]);
            GeneralUtility::sysLog(sprintf('Login-attempt from %s (%s), for username \'%s\' with an empty password!', $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']), 'Core', GeneralUtility::SYSLOG_SEVERITY_WARNING);
            return false;
        }
        */

        //$user = $this->fetchUserRecord($this->login['uname']);
        /** @var \RKW\RkwRegistration\Domain\Repository\FrontendUserRepository $frontendUserRepository */
        //$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        //$frontendUserRepository = $objectManager->get('RKW\\RkwRegistration\\Domain\\Repository\\FrontendUserRepository');
        //$user = $frontendUserRepository->findOneByUsername(strtolower(trim($this->login['uname'])));

        $user = '';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages')->createQueryBuilder();
        $statement = $queryBuilder
            ->select('*')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter(trim($this->login['uname'])))
            )
            ->execute();
        while ($row = $statement->fetch()) {
            $user = $row;
        }

        //DebuggerUtility::var_dump($user);
        if (!is_array($user)) {
            // Failed login attempt (no username found)
            $this->writelog(255, 3, 3, 2, 'Login-attempt from %s (%s), username \'%s\' not found!!', [$this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']]);
            // Logout written to log
            GeneralUtility::sysLog(sprintf('Login-attempt from %s (%s), username \'%s\' not found!', $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']), 'core', GeneralUtility::SYSLOG_SEVERITY_WARNING);
        } else {
            if ($this->writeDevLog) {
                GeneralUtility::devLog('User found: ' . GeneralUtility::arrayToLogString($user, [$this->db_user['userid_column'], $this->db_user['username_column']]), self::class);
            }
        }
        return $user;


    }

    /**
     * LoginUser
     * DANGER: This method authenticates the given user without checking for password!!!
     * @see \RKW\RkwRegistration\Tools\Authentication function validateUser
     * @param array $user
     * @return int >= 200: User authenticated successfully.
     *                     No more checking is needed by other auth services.
     *             >= 100: User not authenticated; this service is not responsible.
     *                     Other auth services will be asked.
     *             > 0:    User authenticated successfully.
     *                     Other auth services will still be asked.
     *             <= 0:   Authentication failed, no more checking needed
     *                     by other auth services.
     */
    public function authUser(array $user): int
    {
        return static::STATUS_AUTHENTICATION_SUCCESS_BREAK;
        //===
    }




    /**
     * Returns TYPO3 settings
     *
     * @param string $which Which type of settings will be loaded
     * @return array
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    protected function getSettings($which = ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS)
    {
        return Common::getTyposcriptConfiguration('Rkwregistration', $which);
        //===
    }
}
