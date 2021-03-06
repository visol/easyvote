<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

/* Votings Dashboard */
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Visol.' . $_EXTKEY,
    'Currentvotings',
    array(
        'VotingDay' => 'showCurrentVotingDay',
        'MetaVotingProposal' => 'show',

    ),
    // non-cacheable actions
    array()
);

/* Navigation of all Kantons */
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Visol.' . $_EXTKEY,
    'Kantonnavigation',
    array(
        'Kanton' => 'kantonNavigation',
    ),
    // non-cacheable actions
    array()
);

/* Voting proposals archive */
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Visol.' . $_EXTKEY,
    'Archive',
    array(
        'MetaVotingProposal' => 'archive,show',
    ),
    // non-cacheable actions
    array(
        'MetaVotingProposal' => 'archive',
    )
);

/* Permalink for meta voting proposals */
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Visol.' . $_EXTKEY,
    'Permalink',
    array(
        'VotingProposal' => 'permalink',
    ),
    // non-cacheable actions
    array(
        'VotingProposal' => 'permalink',
    )
);

/* AJAX functions for community plugins */
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Visol.' . $_EXTKEY,
    'CommunityAjax',
    array(
        'VotingProposal' => 'showPollForVotingProposal,undoUserVoteForVotingProposal,voteForVotingProposal'
    ),
    // non-cacheable actions
    array(
        'VotingProposal' => 'showPollForVotingProposal,undoUserVoteForVotingProposal,voteForVotingProposal'
    )
);

/* Community-Plugins */
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Visol.' . $_EXTKEY,
    'Community',
    array(
        'CommunityUser' => 'userOverview,userFunctions,loginPanel,noProfileNotification,notAuthenticatedModal,profilePicture,create,activate,dataCompletionRequest,appConfiguration',
    ),
    // non-cacheable actions
    array(
        'CommunityUser' => 'userOverview,userFunctions,loginPanel,noProfileNotification,notAuthenticatedModal,profilePicture,create,activate,dataCompletionRequest,appConfiguration',
    )
);

/* Edit Profile */
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Visol.' . $_EXTKEY,
    'Profile',
    array(
        'CommunityUser' => 'editProfile,updateProfile,removeProfile',
    ),
    // non-cacheable actions
    array(
        'CommunityUser' => 'editProfile,updateProfile,removeProfile',
    )
);

/* Eigener Vote-Wecker */
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Visol.' . $_EXTKEY,
    'Notifications',
    array(
        'CommunityUser' => 'editNotifications,updateNotifications',
    ),
    // non-cacheable actions
    array(
        'CommunityUser' => 'editNotifications,updateNotifications',
    )
);

/* Freunde mobilisieren */
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Visol.' . $_EXTKEY,
    'Mobilize',
    array(
        'Event' => 'mobilizations, save, remove',
    ),
    // non-cacheable actions
    array(
        'Event' => 'mobilizations, save, remove',
    )
);

/* Unsubscribe */
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Visol.' . $_EXTKEY,
    'Unsubscribe',
    array(
        'CommunityUser' => 'unsubscribeFromNotification',
    ),
    // non-cacheable actions
    array(
        'CommunityUser' => 'unsubscribeFromNotification',
    )
);

/* Party functions */
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Visol.' . $_EXTKEY,
    'Partyfunctions',
    array(
        'Party' => 'manageMembers,memberFilter,listMembersByDemand',
        'PartyMember' => 'confirm,decline,grantAdmin,remove',
    ),
    // non-cacheable actions
    array(
        'Party' => 'manageMembers,listMembersByDemand',
        'PartyMember' => 'confirm,decline,grantAdmin,remove',
    )
);

/* Election supporter functions */
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Visol.' . $_EXTKEY,
    'Electionsupporterfunctions',
    array(
        'ElectionSupporter' => 'electionSupporterDirectory,filter,listByDemand,follow,unfollow,wall,createEvent',
    ),
    // non-cacheable actions
    array(
        'ElectionSupporter' => 'electionSupporterDirectory,filter,listByDemand,follow,unfollow,createEvent',
    )
);

/* Command Controllers */
if (TYPO3_MODE === 'BE') {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'Visol\\Easyvote\\Command\\SmsMessageProcessorCommandController';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'Visol\\Easyvote\\Command\\EmailMessageProcessorCommandController';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'Visol\\Easyvote\\Command\\ImportCommandController';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'Visol\\Easyvote\\Command\\DataManagerCommandController';
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter('Visol\\Easyvote\\Property\\TypeConverter\\UploadedFileReferenceConverter');
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter('Visol\\Easyvote\\Property\\TypeConverter\\ObjectStorageConverter');

// Register global route
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['routing']['globalRoutes'][] = 'EXT:easyvote/Configuration/GlobalRoutes.yaml';

// Register EID for Postal Code Service
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['easyvote_cityselection'] = 'EXT:easyvote/Resources/Private/Eid/CitySelectionService.php';
