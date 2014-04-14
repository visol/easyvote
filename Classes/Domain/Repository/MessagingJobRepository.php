<?php
namespace Visol\Easyvote\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Lorenz Ulrich <lorenz.ulrich@visol.ch>, visol digitale Dienstleistungen GmbH
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

/**
 *
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class MessagingJobRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

	/**
	 * @param $jobType
	 * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function findPendingJobs($jobType) {
		$query = $this->createQuery();

		$query->matching(
			$query->logicalAnd(
				$query->equals('type', $jobType),
				$query->lessThanOrEqual('distributionTime', \time()),
				$query->equals('timeDistributed', 0),
				$query->equals('timeError', 0)
			)
		);

		return $query->execute();
	}

}
?>