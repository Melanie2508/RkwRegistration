<?php
namespace RKW\RkwRegistration\UserFunctions;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
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
 * Class BaseUrl
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwRegistration
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class BaseUrl
{


    /**
     * Get and check baseUrl (compare with sys_domain)
     * Background: For using the site "Mein RKW" with several domains we have to set the different domains dynamically
     * Objective of this is to have always one top domain for better cookie management
     * (Example: Otherwise we would have non compatible "mein.rkw.de" on the one hand and www.karriereseiten-check.de on the other hand)
     *
     * @param array $data
     * @param array $conf
     * @return string
     */
    public function getCurrent($data = [], $conf = [])
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager objectManager */
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        /** @var \TYPO3\CMS\Frontend\Page\PageRepository $sysPage */
        $pagesRepository = $objectManager->get('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
        $rootLine = $pagesRepository->getRootLine(intval($GLOBALS['TSFE']->id));

        // @toDo: Is array entry 1 always the Website root Pid?
        // maybe we should / chould work here with is_siteroot-Var (not available yet)
        $websiteRootPage = $rootLine[1];

        /** @var \RKW\RkwRegistration\Domain\Repository\SysDomainRepository $sysDomainRepository */
        $sysDomainRepository = $objectManager->get('RKW\\RkwRegistration\\Domain\\Repository\\SysDomainRepository');

        $sysDomain = $sysDomainRepository->findByDomainNameAndPid(strval($_SERVER['HTTP_HOST']), $websiteRootPage['uid'])->getFirst();

        // use $sysDomain entry if given domain is available in this rootPid
        if ($sysDomain instanceof \RKW\RkwRegistration\Domain\Model\SysDomain) {
            return $_SERVER['REQUEST_SCHEME'] . '://' . $sysDomain->getDomainName();
            //===
        }

        // else: Fallback
        return $conf['userFunc.']['baseUrl.']['field'];
        //===

    }



}