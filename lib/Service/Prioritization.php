<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DroneciFastLane\Service;

use Generator;
use OCA\DroneciFastLane\Db\PriorityMapper;
use OCA\DroneciFastLane\Model\Build;
use OCP\DB\Exception;

class Prioritization {
	private PriorityMapper $mapper;
	private Drone $drone;

	public function __construct(PriorityMapper $mapper, Drone $drone) {
		$this->mapper = $mapper;
		$this->drone = $drone;
	}

	/**
	 * @return Generator<Build>
	 * @throws Exception
	 */
	public function getQueue(): Generator {
		$storedBuilds = $this->mapper->getBuilds();
		foreach ($storedBuilds as $storedBuild) {
			$build = $this->drone->getBuildInfo($storedBuild->getNumber(), $storedBuild->getNamespace(), $storedBuild->getRepo());
			if (in_array($build->getStatus(), [Drone::BUILD_STATUS_RUNNING, Drone::BUILD_STATUS_PENDING])) {
				yield $build;
			}
		}
	}
}
