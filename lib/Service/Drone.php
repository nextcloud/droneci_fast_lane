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

use Exception;
use Generator;
use OCA\DroneciFastLane\Entity\DroneBuild;
use OCP\Http\Client\IClientService;
use RuntimeException;
use function json_decode;
use function sprintf;

class Drone {
	public const BUILD_STATUS_RUNNING = 'running';
	public const BUILD_STATUS_PENDING = 'pending';

	protected const API_ENDPOINT_REPOS = '/api/user/repos';
	protected const API_ENDPOINT_BUILDS = '/api/repos/%s/%s/builds';
	protected const API_ENDPOINT_BUILD = '/api/repos/%s/%s/builds/%d';

	private Configuration $configuration;
	private IClientService $httpClientService;
	protected array $baseHeader;

	public function __construct(
		Configuration $configuration,
		IClientService $httpClientService,
	) {
		$this->configuration = $configuration;
		$this->httpClientService = $httpClientService;
	}

	protected function getBaseHeaders(): array {
		if (!isset($this->baseHeader)) {
			$this->baseHeader = [
				'headers' =>
					['Authorization' => 'Bearer ' . $this->configuration->getToken()
					]
			];
		}
		return $this->baseHeader;
	}

	/**
	 * @throws RuntimeException
	 * @returns Generator<string, DroneBuild>
	 */
	public function getBuildQueue(): Generator {
		$client = $this->httpClientService->newClient();

		try {
			$response = $client->get(
				$this->configuration->getHost() . self::API_ENDPOINT_REPOS,
				$this->getBaseHeaders()
			);
			$repoList = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
		} catch (Exception $e) {
			throw new RuntimeException('Error while getting repo list', $e->getCode(), $e);
		}

		foreach ($repoList as $repoItem) {
			if (!$repoItem['active']) {
				// ignore disabled repositories
				continue;
			}

			$genBuild = $this->getBuildList($repoItem['namespace'], $repoItem['name']);
			/**
			 * @var string $key
			 * @var DroneBuild $build
			 */
			foreach ($genBuild as $key => $build) {
				if (in_array($build->getStatus(),
					[self::BUILD_STATUS_PENDING, self::BUILD_STATUS_RUNNING]
				)) {
					yield $key => $build;
				}
			}
		}
	}

	/**
	 * @returns Generator<string, DroneBuild, void, DroneBuild>
	 * @throws RuntimeException
	 */
	protected function getBuildList(string $namespace, string $repo): Generator {
		$client = $this->httpClientService->newClient();

		$endpoint = sprintf(self::API_ENDPOINT_BUILDS, $namespace, $repo);
		try {
			$response = $client->get($this->configuration->getHost() . $endpoint, $this->getBaseHeaders());
			$buildList = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
		} catch (Exception $e) {
			throw new RuntimeException('Error while getting build list', $e->getCode(), $e);
		}

		foreach ($buildList as $buildItem) {
			$buildObj = $this->buildItemToObject($buildItem, $namespace, $repo);
			yield $buildObj->uniqueName() => $buildObj;
		}
	}

	/**
	 * @throws RuntimeException
	 */
	public function getBuildInfo(int $number, string $namespace, string $repo): DroneBuild {
		$client = $this->httpClientService->newClient();

		$endpoint = sprintf(self::API_ENDPOINT_BUILD, $namespace, $repo, $number);
		try {
			$response = $client->get($this->configuration->getHost() . $endpoint, $this->getBaseHeaders());
			$buildItem = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
			return $this->buildItemToObject($buildItem, $namespace, $repo);
		} catch (Exception $e) {
			if ($e->getCode() === 404) {
				$build = new DroneBuild();
				$build->setStatus('x_expired');
				return $build;
			}
			throw new RuntimeException('Error while getting build list', $e->getCode(), $e);
		}
	}

	/**
	 * @throws RuntimeException
	 */
	public function restartBuild(DroneBuild $build): DroneBuild {
		$client = $this->httpClientService->newClient();

		$endpoint = sprintf(self::API_ENDPOINT_BUILD, $build->getNamespace(), $build->getRepo(), $build->getNumber());
		try {
			$client->delete($this->configuration->getHost() . $endpoint, $this->getBaseHeaders());
			$response = $client->post($this->configuration->getHost() . $endpoint, $this->getBaseHeaders());
			$buildItem = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
			return $this->buildItemToObject($buildItem, $build->getNamespace(), $build->getRepo());
		} catch (Exception $e) {
			throw new RuntimeException('Error while restarting build', $e->getCode(), $e);
		}
	}

	protected function buildItemToObject(array $buildItem, string $namespace, string $repo): DroneBuild {
		$build = new DroneBuild();
		$build->setStatus($buildItem['status']);
		$build->setTitle($buildItem['title'] ?? $buildItem['message']);
		$build->setNumber((int)$buildItem['number']);
		$build->setEvent($buildItem['event']);
		$build->setCreatedAt((int)$buildItem['created']);
		$build->setNamespace($namespace);
		$build->setRepo($repo);

		return $build;
	}
}
