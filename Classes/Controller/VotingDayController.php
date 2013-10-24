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

/**
 *
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class VotingDayController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * votingDayRepository
	 *
	 * @var \Visol\Easyvote\Domain\Repository\VotingDayRepository
	 * @inject
	 */
	protected $votingDayRepository;

	/**
	 * metaVotingProposalRepository
	 *
	 * @var \Visol\Easyvote\Domain\Repository\MetaVotingProposalRepository
	 * @inject
	 */
	protected $metaVotingProposalRepository;

	/**
	 * action list
	 *
	 * @unused
	 * @return void
	 */
	public function listAction() {
		$votingDays = $this->votingDayRepository->findAll();
		$this->view->assign('votingDays', $votingDays);
	}

	/**
	 * action showCurrentVotingDay
	 *
	 * @param \Visol\Easyvote\Domain\Model\Kanton $kanton requested Kanton
	 * @return void
	 */
	public function showCurrentVotingDayAction(\Visol\Easyvote\Domain\Model\Kanton $kanton = NULL) {
		$votingDay = $this->votingDayRepository->findCurrentVotingDay();
		// view can be set via TypoScript
		$this->view->setTemplatePathAndFilename(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->settings['votingDayViewTemplate']));
		$this->view->assignMultiple(array(
			'votingDay' => $votingDay,
			'requestedKanton' => $kanton
		));

		$requestArguments = $this->request->getArguments();
		if (isset($requestArguments['selectSingle']) && is_int((int)$requestArguments['selectSingle'])) {
			$this->view->assign('selectMetaVotingProposal', (int)$requestArguments['selectSingle']);
			/** @var \Visol\Easyvote\Domain\Model\MetaVotingProposal $metaVotingPropsal */
			$metaVotingPropsal = $this->metaVotingProposalRepository->findByUid((int)$requestArguments['selectSingle']);
			$votingProposals = $metaVotingPropsal->getVotingProposals()->toArray();
			$firstVotingProposal = $votingProposals[0];
			$this->view->assign('requestedVotingProposal', $firstVotingProposal);
		}

	}

	/**
	 * action show
	 *
	 * @unused
	 * @param \Visol\Easyvote\Domain\Model\VotingDay $votingDay
	 * @return void
	 */
	public function showAction(\Visol\Easyvote\Domain\Model\VotingDay $votingDay) {
		$this->view->assign('votingDay', $votingDay);
	}

}
?>