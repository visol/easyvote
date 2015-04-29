<?php
namespace Visol\Easyvote\Controller;

/**
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

class MetaVotingProposalController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @var \Visol\Easyvote\Domain\Repository\MetaVotingProposalRepository
	 * @inject
	 */
	protected $metaVotingProposalRepository;

	/**
	 * @var \Visol\Easyvote\Domain\Repository\VotingDayRepository
	 * @inject
	 */
	protected $votingDayRepository;

	/**
	 * action list
	 *
	 * @return void
	 */
	public function listAction() {
		$metaVotingProposals = $this->metaVotingProposalRepository->findAll();
		$this->view->assign('metaVotingProposals', $metaVotingProposals);
	}

	/**
	 * action archive
	 *
	 * @return void
	 */
	public function archiveAction() {
		// generate list of all years with votings
		$votingDays = $this->votingDayRepository->findAll();
		$votingYears = array();
		foreach ($votingDays as $votingDay) {
			$votingYear = date('Y', $votingDay->getVotingDate()->getTimestamp());
			$votingYears[$votingYear] = $votingYear;
		}
		$this->view->assign('years', $votingYears);

		// generate list of all Kantons with votings
		$metaVotingProposals = $this->metaVotingProposalRepository->findAll();
		$kantons = array();
		foreach ($metaVotingProposals as $metaVotingProposal) {
			if ($metaVotingProposal->getScope() === 2) {
				// Kantonale Abstimmungen
				$kantons[$metaVotingProposal->getKanton()->getUid()] = $metaVotingProposal->getKanton()->getAbbreviation();
			}
		}
		$this->view->assign('kantons', $kantons);

		// perform search
		$request = $this->request->getArguments();
		$demand = array();
		$filteredRequest = array();

		// search by query string
		if (isset($request['query'])) {
			// we have a search query, so we use it
			$queryString = $GLOBALS['TYPO3_DB']->escapeStrForLike($GLOBALS['TYPO3_DB']->quoteStr($request['query'], 'tx_easyvote_domain_model_metavotingproposal'), 'tx_easyvote_domain_model_metavotingproposal');
			if (!empty($queryString)) {
				$demand['query'] = '%' . $queryString . '%';
				$filteredRequest['query'] = htmlspecialchars($request['query']);
			}
		}

		// filter by national
		if (isset($request['kantons'])) {
			$demand['kantons'] = $request['kantons'];
			$filteredRequest['kantons'] = $request['kantons'];
		}

		// filter by national
		if (isset($request['national'])) {
			$demand['national'] = $request['national'];
			$filteredRequest['national'] = $request['national'];
		}

		// filter by years
		if (isset($request['years'])) {
			$demand['years'] = $request['years'];
			$filteredRequest['years'] = $request['years'];
		}

		$metaVotingProposals = $this->metaVotingProposalRepository->findByDemand($demand);

		$this->view->assign('metaVotingProposals', $metaVotingProposals);
		$this->view->assign('request', $filteredRequest);


	}

	/**
	 * action show
	 *
	 * @param \Visol\Easyvote\Domain\Model\MetaVotingProposal $metaVotingProposal
	 * @return void
	 */
	public function showAction(\Visol\Easyvote\Domain\Model\MetaVotingProposal $metaVotingProposal) {
		$this->view->assign('metaVotingProposal', $metaVotingProposal);
	}

}
