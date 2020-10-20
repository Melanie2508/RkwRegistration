<?php
defined('TYPO3_MODE') || die('Access denied.');

//=================================================================
// Register Plugin
//=================================================================
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'RKW.RkwRegistration',
    'Rkwregistration',
    'RKW Registration'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'RKW.RkwRegistration',
    'RkwregistrationAjax',
    'RKW Registration Ajax'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'RKW.RkwRegistration',
    'RkwregistrationInfo',
    'RKW Registration Info'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'RKW.RkwRegistration',
    'GoBack',
    'RKW Registration: Zurück (Redirect-URL)'
);

//=================================================================
// Add Flexform
//=================================================================
$extKey = 'rkw_registration';
$extensionName = strtolower(\TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($extKey));
$pluginName = strtolower('Rkwregistration');
$pluginSignature = $extensionName.'_'.$pluginName;

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key,pages';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $pluginSignature,
    'FILE:EXT:'.$extKey . '/Configuration/FlexForms/Registration.xml'
);
