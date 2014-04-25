<?php
namespace Visol\Easyvote\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Lorenz Ulrich <lorenz.ulrich@visol.ch>, visol digitale Dienstleistungen GmbH
 *  
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 *
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class CommunityUserController extends \Visol\Easyvote\Controller\AbstractController {

	/**
	 * @var \Visol\Easyvote\Domain\Repository\KantonRepository
	 * @inject
	 */
	protected $kantonRepository;

	/**
	 * @var \Visol\Easyvote\Domain\Repository\VotingDayRepository
	 * @inject
	 */
	protected $votingDayRepository;

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserGroupRepository
	 * @inject
	 */
	protected $frontendUserGroupRepository;

	/**
	 * @var \Visol\Easyvote\Domain\Repository\MessagingJobRepository
	 * @inject
	 */
	protected $messagingJobRepository;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
	 * @inject
	 */
	protected $persistenceManager;

	protected $allowedPhoneNumberPrefixes = array(
		'4175' => '075',
		'4176' => '076',
		'4177' => '077',
		'4178' => '078',
		'4179' => '079'
	);

	/**
	 * action userOverview
	 *
	 * @return void
	 */
	public function userOverviewAction() {
		$communityUser = $this->getLoggedInUser();
		$this->view->assign('user', $communityUser);
	}

	/**
	 * action userFunctions
	 *
	 * @return void
	 */
	public function userFunctionsAction() {
	}

	/**
	 * action loginPanel
	 *
	 * @return void
	 */
	public function loginPanelAction() {
		$communityUser = $this->getLoggedInUser();
		if ($communityUser instanceof \Visol\Easyvote\Domain\Model\CommunityUser) {
			$this->view->assign('user', $communityUser);
		}
	}

	/**
	 * action editProfile
	 */
	public function editProfileAction() {
		$communityUser = $this->getLoggedInUser();
		$kantons = $this->kantonRepository->findAll();

		if ($communityUser instanceof \Visol\Easyvote\Domain\Model\CommunityUser) {
			$fullPhoneNumber = $communityUser->getTelephone();
			$prefixCode = substr($fullPhoneNumber, 0, 4);
			$phoneNumber = substr($fullPhoneNumber, 4);
			$communityUser->setPrefixCode($prefixCode);
			$communityUser->setTelephoneWithoutPrefix($phoneNumber);
			$this->view->assign('user', $communityUser);
			$this->view->assign('kantons', $kantons);
			$this->view->assign('phoneNumberPrefixes', $this->allowedPhoneNumberPrefixes);
		}
	}

	/**
	 * action updateProfile
	 *
	 * @param \Visol\Easyvote\Domain\Model\CommunityUser $communityUser
	 * @param string $phoneNumberPrefix
	 * @dontverifyrequesthash $communityUser
	 */
	public function updateProfileAction(\Visol\Easyvote\Domain\Model\CommunityUser $communityUser, $phoneNumberPrefix = '4175') {
		$loggedInUser = $this->getLoggedInUser();
		/** Todo: Sanitize properties that should never be updated by the user. */
		if ($loggedInUser->getUid() === $communityUser->getUid()) {
			if (array_key_exists($phoneNumberPrefix, $this->allowedPhoneNumberPrefixes)) {
				$communityUser->setTelephone($phoneNumberPrefix . preg_replace('/\D/', '', $communityUser->getTelephone()));
			} else {
				$communityUser->setTelephone($this->allowedPhoneNumberPrefixes[0] . preg_replace('/\D/', '', $communityUser->getTelephone()));
			}
			$this->communityUserRepository->update($communityUser);
			$this->persistenceManager->persistAll();
			$this->flashMessageContainer->add('');
			$this->flashMessageContainer->add(LocalizationUtility::translate('editProfile.saved', 'easyvote'));
		}
		$this->redirect('editProfile');
	}

	/**
	 * action removeProfile
	 *
	 * @param \Visol\Easyvote\Domain\Model\CommunityUser $communityUser
	 * @dontvalidate $communityUser
	 */
	public function removeProfileAction(\Visol\Easyvote\Domain\Model\CommunityUser $communityUser) {
		$loggedInUser = $this->getLoggedInUser();
		if ($loggedInUser->getUid() === $communityUser->getUid()) {
			$communityUser->setEmail('');
			$communityUser->setNotificationMailActive(0);
			$communityUser->setTelephone('');
			$communityUser->setNotificationSmsActive(0);
			$communityUser->setUsername('gelöschter Benutzer');
			$communityUser->setFirstName('gelöschter Benutzer');
			$communityUser->setLastName('');
			$communityUser->setAddress('');
			$communityUser->setZip('');
			$communityUser->setCity('');
			$communityUserGroup = $this->frontendUserGroupRepository->findByUid($this->settings['communityUserGroupUid']);
			$communityUser->removeUsergroup($communityUserGroup);
			$this->communityUserRepository->update($communityUser);
			// get all notificationRelatedUsers and remove them as well
			$notificationRelatedUsers = $communityUser->getNotificationRelatedUsers();
			foreach ($notificationRelatedUsers as $notificationRelatedUser) {
				$this->communityUserRepository->remove($notificationRelatedUser);
			}
			$this->persistenceManager->persistAll();
			$siteHomeUri = $this->uriBuilder->setTargetPageUid($this->settings['siteHomePid'])->setArguments(array('logintype' => 'logout'))->setCreateAbsoluteUri(TRUE)->build();
			$arguments = array('redirectUri' => $siteHomeUri);
			$this->redirect('revokePermissions', 'Authentication', 'Vifbauth', $arguments, $this->settings['loginPid']);
		}
	}

	/**
	 * action editNotifications
	 */
	public function editNotificationsAction() {
		$communityUser = $this->getLoggedInUser();
		if ($communityUser instanceof \Visol\Easyvote\Domain\Model\CommunityUser) {
			$fullPhoneNumber = $communityUser->getTelephone();
			$prefixCode = substr($fullPhoneNumber, 0, 4);
			$phoneNumber = substr($fullPhoneNumber, 4);
			$communityUser->setPrefixCode($prefixCode);
			$communityUser->setTelephoneWithoutPrefix($phoneNumber);
			$this->view->assign('user', $communityUser);
			$this->view->assign('phoneNumberPrefixes', $this->allowedPhoneNumberPrefixes);
		}
	}

	/**
	 * action updateNotifications
	 *
	 * @param \Visol\Easyvote\Domain\Model\CommunityUser $communityUser
	 * @param string $phoneNumberPrefix
	 * @dontverifyrequesthash $communityUser
	 * @dontvalidate $communityUser
	 */
	public function updateNotificationsAction(\Visol\Easyvote\Domain\Model\CommunityUser $communityUser, $phoneNumberPrefix = '4175') {
		$loggedInUser = $this->getLoggedInUser();
		/** Todo: Sanitize properties that should never be updated by the user. */
		if ($loggedInUser->getUid() === $communityUser->getUid()) {
			if (array_key_exists($phoneNumberPrefix, $this->allowedPhoneNumberPrefixes)) {
				$communityUser->setTelephone($phoneNumberPrefix . preg_replace('/\D/', '', $communityUser->getTelephone()));
			} else {
				$communityUser->setTelephone($this->allowedPhoneNumberPrefixes[0] . preg_replace('/\D/', '', $communityUser->getTelephone()));
			}
			$this->communityUserRepository->update($communityUser);
			$this->persistenceManager->persistAll();
			$this->flashMessageContainer->add(LocalizationUtility::translate('editNotifications.saved', 'easyvote'));

		}
		$this->redirect('editNotifications');
	}

	/**
	 * action editNotifications
	 */
	public function editMobilizationsAction() {
		$communityUser = $this->getLoggedInUser();
		$nextVotingDay = $this->votingDayRepository->findNextVotingDay();
		if ($communityUser instanceof \Visol\Easyvote\Domain\Model\CommunityUser) {
			$this->view->assign('user', $communityUser);
			$this->view->assign('nextVotingDay', $nextVotingDay);
		}
	}

	/**
	 * action listMobilizedCommunityUsers
	 *
	 * @return string|boolean
	 */
	public function listMobilizedCommunityUsersAction() {
		$communityUser = $this->getLoggedInUser();
		if ($communityUser instanceof \Visol\Easyvote\Domain\Model\CommunityUser) {
			$this->view->assign('user', $communityUser);
			$content = $this->view->render();
			return json_encode($content);
		} else {
			return json_encode(FALSE);
		}
	}

	/**
	 * action newMobilizedCommunityUser
	 *
	 * @return string
	 */
	public function newMobilizedCommunityUserAction() {
		$newCommunityUser = $this->objectManager->create('Visol\Easyvote\Domain\Model\CommunityUser');
		$this->view->assign('newCommunityUser', $newCommunityUser);
		$content = $this->view->render();
		return json_encode($content);
	}

	/**
	 * action createMobilizedCommunityUser
	 *
	 * @param \Visol\Easyvote\Domain\Model\CommunityUser $newCommunityUser
	 * @dontverifyrequesthash $newCommunityUser
	 * @dontvalidate $newCommunityUser
	 * @return string
	 */
	public function createMobilizedCommunityUserAction(\Visol\Easyvote\Domain\Model\CommunityUser $newCommunityUser) {
		$communityUser = $this->getLoggedInUser();
		if ($communityUser instanceof \Visol\Easyvote\Domain\Model\CommunityUser) {
			// user is authorized to add users to his profile
			if (GeneralUtility::validEmail($newCommunityUser->getEmail())) {
				if (!count($this->communityUserRepository->findByEmail($newCommunityUser->getEmail()))) {
					$notificationRelatedUserGroupUid = (int)$this->settings['notificationRelatedUserGroupUid'];
					$notificationRelatedUserGroup = $this->frontendUserGroupRepository->findByUid($notificationRelatedUserGroupUid);
					$newCommunityUser->addUsergroup($notificationRelatedUserGroup);
					$newCommunityUser->setCommunityUser($communityUser);
					$newCommunityUser->setNotificationMailActive(1);
					$newCommunityUser->setUsername($newCommunityUser->getEmail());
					$newCommunityUser->setPassword(md5(GeneralUtility::generateRandomBytes(40)));
					$newCommunityUser->setPid($this->settings['userStoragePid']);
					$this->communityUserRepository->add($newCommunityUser);
					$this->persistenceManager->persistAll();

					/** @var \Visol\Easyvote\Domain\Model\MessagingJob $messagingJob */
					$standaloneView = $this->objectManager->create('TYPO3\CMS\Fluid\View\StandaloneView');
					$standaloneView->setFormat('html');
					$extbaseConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, 'easyvote', 'easyvote');
					$templateRootPath = GeneralUtility::getFileAbsFileName($extbaseConfiguration['view']['templateRootPath']);
					$templatePathAndFilename = $templateRootPath . 'Email/MobilizedWelcomeMail.html';
					$standaloneView->setTemplatePathAndFilename($templatePathAndFilename);
					$nextVotingDay = $this->votingDayRepository->findNextVotingDay();
					$standaloneView->assign('nextVotingDay', $nextVotingDay);
					$standaloneView->assign('parentUser', $communityUser);
					$standaloneView->assign('mobilizedUser', $newCommunityUser);
					$content = $standaloneView->render();
					$messagingJob = $this->objectManager->create('Visol\Easyvote\Domain\Model\MessagingJob');
					$messagingJob->setContent($content);
					$messagingJob->setSubject(LocalizationUtility::translate('mobilizedWelcomeMail.subject', 'easyvote'));
					$messagingJob->setCommunityUser($newCommunityUser);
					$messagingJob->setDistributionTime(new \DateTime());
					$messagingJob->setType($messagingJob::JOBTYPE_EMAIL);
					$this->messagingJobRepository->add($messagingJob);

					return json_encode(LocalizationUtility::translate('editMobilizations.saved', 'easyvote'));
				} else {
					return json_encode(LocalizationUtility::translate('editMobilizations.notSavedAlreadyMobilized', 'easyvote'));
				}
			} else {
				// e-mail invalid
				return json_encode(LocalizationUtility::translate('editMobilizations.notSavedEmailInvalid', 'easyvote'));
			}
		} else {
			return json_encode(FALSE);
		}
	}

	/**
	 * action removeMobilizedCommunityUser
	 *
	 * @param \Visol\Easyvote\Domain\Model\CommunityUser $notificationRelatedUser
	 * @dontverifyrequesthash $notificationRelatedUser
	 * @dontvalidate $notificationRelatedUser
	 * @return string
	 */
	public function removeMobilizedCommunityUserAction(\Visol\Easyvote\Domain\Model\CommunityUser $notificationRelatedUser) {
		$communityUser = $this->getLoggedInUser();
		if ($communityUser instanceof \Visol\Easyvote\Domain\Model\CommunityUser) {
			// user is logged in
			if ($notificationRelatedUser->getCommunityUser()->getUid() === $communityUser->getUid()) {
				// is it really a child of the logged in user
				// TODO in future: Check is user has change account to a real account and prevent deletion
				$this->communityUserRepository->remove($notificationRelatedUser);
				$this->persistenceManager->persistAll();
				return json_encode(LocalizationUtility::translate('editMobilizations.removed', 'easyvote'));
			} else {
				return json_encode(LocalizationUtility::translate('editMobilizations.permissionError', 'easyvote'));
			}
		} else {
			return json_encode(LocalizationUtility::translate('editMobilizations.notAuthenticated', 'easyvote'));
		}
	}

	public function backendDashboardAction() {

	}

	/**
	 * Interface for sending SMS messages
	 */
	public function backendSmsMessagingIndexAction() {
		$languages = array(
			'Deutsch' => \Visol\Easyvote\Domain\Model\CommunityUser::USERLANGUAGE_GERMAN,
			'Französisch' => \Visol\Easyvote\Domain\Model\CommunityUser::USERLANGUAGE_FRENCH,
			'Italienisch' => \Visol\Easyvote\Domain\Model\CommunityUser::USERLANGUAGE_ITALIAN
		);

		$kantons = $this->kantonRepository->findAll();

		$dateTime = new \DateTime();
		$dateTime = $dateTime->format('Y-m-d\TH:i:s');

		$testUser = $this->communityUserRepository->findByUid($this->settings['smsTestUserUid']);

		$this->view->assignMultiple(array(
			'languages' => $languages,
			'kantons' => $kantons,
			'dateTime' => $dateTime,
			'testUser' => $testUser
		));

	}

	/**
	 * @param array $demand
	 */
	public function backendSmsMessageSendAction($demand) {
		if ((int)$demand['sendToTestUser'] === 1) {
			// no need to process filter demand, we just queue one message
			$messagingJob = new \Visol\Easyvote\Domain\Model\MessagingJob();
			$messagingJob->setContent($demand['message']);
			$messagingJob->setType(\Visol\Easyvote\Domain\Model\MessagingJob::JOBTYPE_SMS);
			$distributionTime = date_create($demand['distrubutionTime']);
			$messagingJob->setDistributionTime($distributionTime);
			$testUser = $this->communityUserRepository->findByUid($this->settings['smsTestUserUid']);
			$messagingJob->setCommunityUser($testUser);
			$messagingJob->setSubject('SMS-Test-Job');
			$this->messagingJobRepository->add($messagingJob);
			$this->flashMessageContainer->add('Test-SMS wurde in Warteschlange gestellt.');
		} else {
			// we queue the message for all users
			if (is_array($demand['filter'])) {
				$demand['filter']['type'] = \Visol\Easyvote\Domain\Model\MessagingJob::JOBTYPE_SMS;
				$communityUsers = $this->communityUserRepository->findByFilterDemand($demand['filter']);
				$distributionTime = date_create($demand['distrubutionTime']);
				$jobRandomValue = uniqid();
				$iterator = 1;
				foreach ($communityUsers as $communityUser) {
					$messagingJob = new \Visol\Easyvote\Domain\Model\MessagingJob();
					$messagingJob->setContent($demand['message']);
					$messagingJob->setType(\Visol\Easyvote\Domain\Model\MessagingJob::JOBTYPE_SMS);
					$messagingJob->setDistributionTime($distributionTime);
					$messagingJob->setCommunityUser($communityUser);
					$messagingJob->setSubject('SMS-Job ' . $jobRandomValue);
					$this->messagingJobRepository->add($messagingJob);
					if ($iterator % 20 == 0) {
						$this->persistenceManager->persistAll();
					}
					$iterator++;
				}
				$communityUsersCount = $communityUsers->count();
				$this->flashMessageContainer->add('Es wurden ' . $communityUsersCount . ' SMS in die Warteschlange gestellt.');
			} else {
				$this->flashMessageContainer->add('Fehler: Keine Filter-Anfrage für Versand.', '', \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
			}
		}
		$this->persistenceManager->persistAll();
		$this->redirect('backendSmsMessagingIndex');
	}

	/**
	 * Deactivate errorFlashMessage
	 *
	 * @return bool|string
	 */
	public function getErrorFlashMessage() {
		return FALSE;
	}


}
?>