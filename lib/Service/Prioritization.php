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
use OCA\DroneciFastLane\AppInfo\Application;
use OCA\DroneciFastLane\Db\PriorityMapper;
use OCA\DroneciFastLane\Entity\BasicBuild;
use OCA\DroneciFastLane\Entity\DroneBuild;
use OCP\DB\Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Prioritization {
	private PriorityMapper $mapper;
	private Drone $drone;
	private LoggerInterface $logger;

	public function __construct(PriorityMapper $mapper, Drone $drone, LoggerInterface $logger) {
		$this->mapper = $mapper;
		$this->drone = $drone;
		$this->logger = $logger;
	}

	public function setPrioritized(string $slug, int $buildNumber): bool {
		try {
			foreach ($this->mapper->getBuilds() as $build) {
				if ($build->getNumber() === $buildNumber
					&& $slug === $build->getSlug()
				) {
					// already prioritized
					return true;
				}
			}

			[$namespace, $repo] = explode('/', $slug);
			$build = $this->drone->getBuildInfo($buildNumber, $namespace, $repo);
			$prioBuild = BasicBuild::fromDerivedBuild($build);
			$this->mapper->insert($prioBuild);
		} catch (RuntimeException|Exception) {
			return false;
		}

		return true;
	}

	/**
	 * @throws Exception
	 */
	public function reorganizeQueue(): void {
		$oldestPrioBuildTime = 0;
		$prioritizedBuilds = [];

		foreach ($this->mapper->getBuilds() as $build) {
			$oldestPrioBuildTime = max($oldestPrioBuildTime, $build->getCreatedAt());
			$prioritizedBuilds[$build->uniqueName()] = true;
		}

		$buildQueueGen = $this->drone->getBuildQueue();
		foreach ($buildQueueGen as $build) {
			if (isset($prioritizedBuilds[$build->uniqueName()])
				|| $build->getCreatedAt() > $oldestPrioBuildTime
				|| $build->getStatus() === Drone::BUILD_STATUS_RUNNING
			) {
				continue;
			}
			try {
				$this->logger->info('Restarting build {build}', [
					'app' => Application::APP_ID,
					'build' => $build->uniqueName(),
				]);
				$this->drone->restartBuild($build);
			} catch (RuntimeException $e) {
				$this->logger->error('An error occurred while restarting build {build}', [
					'app' => Application::APP_ID,
					'build' => $build->uniqueName(),
					'exception' => $e,
				]);
			}
		}
	}

	/**
	 * @return Generator<string, DroneBuild>
	 * @throws RuntimeException
	 */
	public function getQueue(): Generator {
		$storedBuilds = $this->mapper->getBuilds();
		foreach ($storedBuilds as $storedBuild) {
			$build = $this->drone->getBuildInfo($storedBuild->getNumber(), $storedBuild->getNamespace(), $storedBuild->getRepo());
			if (in_array($build->getStatus(), [Drone::BUILD_STATUS_RUNNING, Drone::BUILD_STATUS_PENDING])) {
				yield $build->uniqueName() => $build;
			}
		}
	}
}
