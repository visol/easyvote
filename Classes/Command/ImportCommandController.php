<?php
namespace Visol\Easyvote\Command;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Visol\Easyvote\Domain\Model\CommunityUser;

/**
 * Controller
 */
class ImportCommandController extends \Visol\Easyvote\Command\AbstractCommandController
{

    /**
     * @var \Visol\Easyvote\Domain\Repository\CityRepository
     * @inject
     */
    protected $cityRepository;

    /**
     * @var \Visol\Easyvote\Domain\Repository\KantonRepository
     * @inject
     */
    protected $kantonRepository;

    /**
     * @var \Visol\Easyvote\Domain\Repository\CommunityUserRepository
     * @inject
     */
    protected $communityUserRepository;

    /**
     * @var \Visol\Easyvote\Domain\Repository\EventRepository
     * @inject
     */
    protected $eventRepository;

    /**
     * persistenceManager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     * @inject
     */
    protected $persistenceManager;

    /**
     * Import City and Postal Code data
     *
     * @param string $sourcePathAndFilename File to import
     */
    public function importCityDataCommand($sourcePathAndFilename)
    {
        if (is_file($sourcePathAndFilename)) {
            $handle = fopen($sourcePathAndFilename, 'r');
            $i = 0;
            $messages = array();
            $messages[] = 'Importiere Daten aus Datei ' . $sourcePathAndFilename . LF;
            while (($data = fgetcsv($handle, 0, ';')) !== FALSE) {
                $i++;
                if ($i == 1) {
                    continue;
                }
                if ($data[4] === 'LI') {
                    // Ignore Fürstentum Liechtenstein
                    continue;
                }
                if (empty($data[4])) {
                    // Don't import cities without Kanton
                    continue;
                }
                $existingCity = $this->cityRepository->findOneByNamePostalCodeMunicipality(utf8_encode($data[0]), $data[1], utf8_encode($data[3]));
                if ($existingCity instanceof \Visol\Easyvote\Domain\Model\City) {
                    /** @var $existingCity \Visol\Easyvote\Domain\Model\City */
                    $existingCity->setLongitude($data[5]);
                    $existingCity->setLatitude($data[6]);
                    /** @var \Visol\Easyvote\Domain\Model\Kanton $kanton */
                    $kanton = $this->kantonRepository->findOneByAbbreviation($data[4]);
                    if ($kanton instanceof \Visol\Easyvote\Domain\Model\Kanton) {
                        $kanton->addCity($existingCity);
                        $existingCity->setKanton($kanton);
                        $this->kantonRepository->update($kanton);
                    } else {
                        $messages[] = '!!! Kein Kanton gefunden für Gemeinde ' . $existingCity->getName() . '.' . LF;
                    }
                    $this->cityRepository->update($existingCity);
                    $messages[] = 'Gemeinde ' . $existingCity->getName() . ' aktualisiert.' . LF;

                } else {
                    $city = new \Visol\Easyvote\Domain\Model\City;
                    $city->setName(utf8_encode($data[0]));
                    $city->setPostalCode($data[1]);
                    $city->setMunicipality(utf8_encode($data[3]));
                    $city->setLongitude($data[5]);
                    $city->setLatitude($data[6]);
                    if (!empty($data[4])) {
                        /** @var \Visol\Easyvote\Domain\Model\Kanton $kanton */
                        $kanton = $this->kantonRepository->findOneByAbbreviation(utf8_encode($data[4]));
                        $kanton->addCity($city);
                        $city->setKanton($kanton);
                        $this->kantonRepository->update($kanton);
                    } else {
                        $messages[] = '!!! Kein Kanton gefunden für Gemeinde ' . $city->getName() . '.' . LF;
                    }
                    $this->cityRepository->add($city);
                    $messages[] = 'Gemeinde ' . $city->getName() . ' importiert.' . LF;

                }

                $this->persistenceManager->persistAll();

            }

            $this->response->setContent(implode($messages));


        } else {
            $this->response->setContent('File "' . $sourcePathAndFilename . '" not found' . LF);
            $this->response->setExitCode(2);
        }

    }

    /**
     * Generate an auth token for each community user and update the community user with it
     */
    public function generateAuthTokenForCommunityUsersCommand()
    {
        $communityUsers = $this->communityUserRepository->findAll();
        $i = 0;
        foreach ($communityUsers as $communityUser) {
            /** @var \Visol\Easyvote\Domain\Model\CommunityUser $communityUser */
            $token = \Visol\Easyvote\Utility\Algorithms::generateRandomToken(20);
            $communityUser->setAuthToken($token);
            $this->communityUserRepository->update($communityUser);
            if ($i % 50) {
                $this->persistenceManager->persistAll();
            }
            $i++;
        }
        $this->persistenceManager->persistAll();
    }

    /**
     * Generates an Event for each suspected Vote Hero
     *
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function generateEventForCommunityNewsRecipientsCommand()
    {
        $communityNewsRecipients = $this->communityUserRepository->findCommunityNewsRecipientsWithoutEvent();
        foreach ($communityNewsRecipients as $communityUser) {
            /** @var $communityUser \Visol\Easyvote\Domain\Model\CommunityUser */

            $this->outputLine('Generate event for ' . $communityUser->getEmail());

            /** @var \Visol\Easyvote\Domain\Model\Event $event */
            $event = $this->objectManager->get('Visol\Easyvote\Domain\Model\Event');
            $event->setDate(new \DateTime('2015-10-08'));
            $event->setCommunityUser($communityUser);
            $this->eventRepository->add($event);
            $this->persistenceManager->persistAll();
        }
        $this->communityUserRepository->updateRelationCount('tx_easyvote_domain_model_event', 'community_user', 'events', 'fe_users', array('deleted', 'disable'));
    }

    /**
     * Generates an Event for each suspected Vote Hero
     *
     * @param string $communityUsers CSV list of Community Users
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function generateEventForCommunityUsersCommand($communityUsers)
    {
        $communityUsers = GeneralUtility::trimExplode(',', $communityUsers);
        foreach ($communityUsers as $communityUserUid) {
            /** @var $communityUser \Visol\Easyvote\Domain\Model\CommunityUser */
            $communityUser = $this->communityUserRepository->findByUid((int)$communityUserUid);

            if ($communityUser instanceof CommunityUser && $communityUser->getEvents()->count() === 0) {
                $this->outputLine('Generate event for ' . $communityUser->getEmail());
                /** @var \Visol\Easyvote\Domain\Model\Event $event */
                $event = $this->objectManager->get('Visol\Easyvote\Domain\Model\Event');
                $event->setDate(new \DateTime('2015-10-08'));
                $event->setCommunityUser($communityUser);
                $this->eventRepository->add($event);
                $this->persistenceManager->persistAll();
            } else {
                $this->outputLine('User with the following uid not found: ' . $communityUserUid);
            }
        }
        $this->communityUserRepository->updateRelationCount('tx_easyvote_domain_model_event', 'community_user', 'events', 'fe_users', array('deleted', 'disable'));
        $this->sendAndExit();
    }
}
