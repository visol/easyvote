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

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 *
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class CityController extends ActionController
{

    /**
     * kantonRepository
     *
     * @var \Visol\Easyvote\Domain\Repository\KantonRepository
     * @inject
     */
    protected $kantonRepository;

    /**
     * cityRepository
     *
     * @var \Visol\Easyvote\Domain\Repository\CityRepository
     * @inject
     */
    protected $cityRepository;

}
