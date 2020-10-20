<?php

namespace RKW\RkwRegistration\Domain\Repository;
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
 * SysDomainRepository
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwRegistration
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SysDomainRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /*
     * initializeObject
     */
    public function initializeObject()
    {
        $this->defaultQuerySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');
        $this->defaultQuerySettings->setRespectStoragePage(false);
        $this->defaultQuerySettings->setRespectSysLanguage(false);
    }



    /**
     * function findByDomainNameAndPid
     *
     * @param string $domainName
     * @param integer $pid
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findByDomainNameAndPid($domainName, $pid)
    {

        $query = $this->createQuery();
        $services = $query
            ->matching(
                $query->logicalAnd(
                    $query->equals('domainName', $domainName),
                    $query->equals('pid', $pid)
                )
            )
            ->execute();

        return $services;
        //===
    }



}