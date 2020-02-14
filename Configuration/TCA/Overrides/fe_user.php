<?php
$tempCols = [

	'tx_rkwregistration_mobile' => [
		'label'=>'LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.tx_rkwregistration_mobile',
		'exclude' => 0,
		'config'=>[
			'type'=>'input',
            'size' => 20,
            'max' => '256',
            'eval' => 'trim'
		],
	],

    'tx_rkwregistration_gender' => [
        'label'=>'LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.tx_rkwregistration_gender',
        'exclude' => 0,
        'config'=>[
			'type' => 'select',
            'renderType' => 'selectSingle',
			'minitems' => 0,
			'maxitems' => 1,
			'default' => 99,
			'items' => [
				['LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.tx_rkwregistration_gender.I.0', '0'],
				['LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.tx_rkwregistration_gender.I.1', '1'],
                ['LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.tx_rkwregistration_gender.I.99', '99'],

            ],
        ],
    ],

	'title' => [
		'label'=>'LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.title',
		'exclude' => 1,
		'config' => [
            'type'=>'input',
            'size' => 20,
            'max' => '256',
            'eval' => 'trim',
		],
	],

    'tx_rkwregistration_title' => [
        'label'=>'LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.tx_rkwregistration_title',
        'exclude' => 0,
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'foreign_table' => 'tx_rkwregistration_domain_model_title',
            'foreign_table_where' => 'AND tx_rkwregistration_domain_model_title.hidden = 0 AND tx_rkwregistration_domain_model_title.deleted = 0 ORDER BY name ASC',
            'MM' => 'tx_rkwregistration_title_record_mm',
            'MM_opposite_field' => 'items',
            'MM_match_fields' => [
                'tablenames' => 'fe_users',
            ],
        ],
    ],

	'tx_rkwregistration_twitter_id' => [
        'label'=>'LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.tx_rkwregistration_twitter_id',
        'exclude' => 0,
        'config'=>[
            'type'=>'input',
            'size' => 30,
            'max' => '256',
            'eval' => 'trim'
        ],
    ],

    'tx_rkwregistration_twitter_url' => [
        'label'=>'LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.tx_rkwregistration_twitter_url',
        'exclude' => 0,
        'config'=>[
            'type'=>'input',
            'size' => 30,
            'max' => '256',
            'eval' => 'trim',
            'wizards' => [
                'link' => [
                    'type' => 'popup',
                    'title' => 'LLL:EXT:cms/locallang_ttc.xlf:header_link_formlabel',
                    'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_link.gif',
                    'module' => [
                        'name' => 'wizard_link',
                        'urlParameters' => [
                            'mode' => 'wizard',
                        ],
                    ],
                    'JSopenParams' => 'height=400,width=550,status=0,menubar=0,scrollbars=1',
                    'params' => [
                        // List of tabs to hide in link window. Allowed values are:
                        // file, mail, page, spec, folder, url
                        'blindLinkOptions' => 'mail,file,page,spec,folder',

                        // allowed extensions for file
                        //'allowedExtensions' => 'mp3,ogg',
                    ],
                ],
            ],
            'softref' => 'typolink'
        ],
    ],

    'tx_rkwregistration_facebook_id' => [
		'label'=>'LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.tx_rkwregistration_facebook_id',
		'exclude' => 0,
		'config'=>[
			'type'=>'input',
            'size' => 30,
            'max' => '256',
            'eval' => 'trim'
		],
	],

    'tx_rkwregistration_facebook_url' => [
        'label'=>'LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.tx_rkwregistration_facebook_url',
        'exclude' => 0,
        'config'=>[
            'type'=>'input',
            'size' => 30,
            'max' => '256',
            'eval' => 'trim',
            'wizards' => [
                'link' => [
                    'type' => 'popup',
                    'title' => 'LLL:EXT:cms/locallang_ttc.xlf:header_link_formlabel',
                    'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_link.gif',
                    'module' => [
                        'name' => 'wizard_link',
                        'urlParameters' => [
                            'mode' => 'wizard',
                        ],
                    ],
                    'JSopenParams' => 'height=400,width=550,status=0,menubar=0,scrollbars=1',
                    'params' => [
                        // List of tabs to hide in link window. Allowed values are:
                        // file, mail, page, spec, folder, url
                        'blindLinkOptions' => 'mail,file,page,spec,folder',

                        // allowed extensions for file
                        //'allowedExtensions' => 'mp3,ogg',
                    ],
                ],
            ],
            'softref' => 'typolink'
        ],
    ],

	'tx_rkwregistration_xing_url' => [
		'label'=>'LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.tx_rkwregistration_xing_url',
		'exclude' => 0,
		'config'=>[
			'type'=>'input',
            'size' => 30,
            'max' => '256',
            'eval' => 'trim',
            'wizards' => [
                'link' => [
                    'type' => 'popup',
                    'title' => 'LLL:EXT:cms/locallang_ttc.xlf:header_link_formlabel',
                    'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_link.gif',
                    'module' => [
                        'name' => 'wizard_link',
                        'urlParameters' => [
                            'mode' => 'wizard',
                        ],
                    ],
                    'JSopenParams' => 'height=400,width=550,status=0,menubar=0,scrollbars=1',
                    'params' => [
                        // List of tabs to hide in link window. Allowed values are:
                        // file, mail, page, spec, folder, url
                        'blindLinkOptions' => 'mail,file,page,spec,folder',

                        // allowed extensions for file
                        //'allowedExtensions' => 'mp3,ogg',
                    ],
                ],
            ],
            'softref' => 'typolink'
		],
	],


    'tx_rkwregistration_registered_by' => [
        'label'=>'LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.tx_rkwregistration_registered_by',
        'exclude' => 0,
		'config'=>[
			'type' => 'select',
            'renderType' => 'selectSingle',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
			'default' => 0,
            'readOnly' => 1,
			'items' => [
				['LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.tx_rkwregistration_registered_by.I.rkw', '0'],
				['LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.tx_rkwregistration_registered_by.I.facebook', '1'],
				['LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.tx_rkwregistration_registered_by.I.twitter', '2'],
				['LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.tx_rkwregistration_registered_by.I.xing', '3'],
			],
		],
    ],

    'tx_rkwregistration_register_remote_ip' => [
        'label'=>'LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.tx_rkwregistration_register_remote_ip',
        'exclude' => 0,
        'config'=>[
            'type'=>'input',
            'readOnly' => 1,
            'size' => 30,
            'max' => '256',
            'eval' => 'trim',
        ],
    ],

    'tx_rkwregistration_login_error_count' => [
        'label'=>'LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.tx_rkwregistration_login_error_count',
        'exclude' => 0,
        'config'=>[
            'type'=>'input',
            'size' => 20,
            'max' => '256',
            'eval' => 'trim,int'
        ],
    ],

    'tx_rkwregistration_language_key' => [
        'label'=>'LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.tx_rkwregistration_language_key',
        'exclude' => 0,
        'config'=>[
            'type'=>'input',
            'size' => 20,
            'max' => '256',
            'eval' => 'trim',
        ],
    ],

	'tx_rkwregistration_is_anonymous' => [
		'label'=>'LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.tx_rkwregistration_is_anonymous',
		'exclude' => 0,
		'config'=>[
			'type' => 'check',
			'readOnly' => 1,
			'default' => 0,
			'items' => [
				'1' => [
					'0' => 'LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.tx_rkwregistration_is_anonymous'
				],
			],
		],
	],

    'tx_rkwregistration_cross_domain_token' => [
        'config'=>[
            'type' => 'passthrough',
        ],
    ],

    'tx_rkwregistration_cross_domain_token_tstamp' => [
        'config'=>[
            'type' => 'passthrough',
        ],
    ],

    'tx_rkwregistration_data_protection_status' => [
        'config'=>[
            'type' => 'passthrough',
        ],
    ],

	# this entry is to show the users privacy entries in backend
	# this field does not exist in the database, because this is not necessary. This uni-directional relation works fine
	'tx_rkwregistration_privacy' => [
		'exclude' => 1,
		'label' => 'LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.tx_rkwregistration_privacy',
		'config' => [
			'type' => 'inline',
			'foreign_table' => 'tx_rkwregistration_domain_model_privacy',
			'foreign_field' => 'frontend_user',
			'foreign_match_fields' => [
				'parent' => 0
			],
			'maxitems'      => 9999,
			'appearance' => [
				'collapseAll' => 1,
				'levelLinksPosition' => 'top',
				'showSynchronizationLink' => 1,
				'showPossibleLocalizationRecords' => 1,
				'showAllLocalizationLink' => 1,
				'enabledControls' => [
					'new' => FALSE,
				],
			],
		],
	],
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users',$tempCols);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users','--div--;LLL:EXT:rkw_registration/Resources/Private/Language/locallang_db.xlf:tx_rkwregistration_domain_model_frontenduser.socialmedia,tx_rkwregistration_twitter_id', '', '');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users','tx_rkwregistration_twitter_url,  tx_rkwregistration_facebook_id, tx_rkwregistration_facebook_url, tx_rkwregistration_xing_url', '', 'after:tx_rkwregistration_twitter_id');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users','tx_rkwregistration_title','','after:title');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users','tx_rkwregistration_gender','','before:name');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users','tx_rkwregistration_mobile','','after:telephone');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users','tx_rkwregistration_federal_state','','after:city');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users','tx_rkwregistration_login_error_count','','after:disable');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users',', tx_rkwregistration_registered_by, tx_rkwregistration_register_remote_ip, tx_rkwregistration_language_key','','after:lockToDomain');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users',', tx_rkwregistration_is_anonymous','','after:lockToDomain');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users',', tx_rkwregistration_privacy','','after:image');

