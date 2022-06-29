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
use RuntimeException;
use OCA\DroneciFastLane\Model\Build;
use OCP\Http\Client\IClientService;
use function json_decode;
use function sprintf;

class Drone {
	public const BUILD_STATUS_RUNNING = 'running';
	public const BUILD_STATUS_PENDING = 'pending';

	protected const API_ENDPOINT_REPOS = '/api/user/repos';
	protected const API_ENDPOINT_BUILDS = '/api/repos/%s/%s/builds';

	private Configuration $configuration;
	private IClientService $httpClientService;
	protected array $baseHeader;

	public function __construct(
		Configuration $configuration,
		IClientService $httpClientService
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
	 * @throws Exception
	 * @return Build[]
	 */
	public function getBuildQueue(): array {
		$client = $this->httpClientService->newClient();

		$response = $client->get(
			$this->configuration->getHost() . self::API_ENDPOINT_REPOS,
			$this->getBaseHeaders()
		);

		$repoList = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
		$buildList = [];
		foreach ($repoList as $repoItem) {
			if(!$repoItem['active']) {
				// ignore disabled repositories
				continue;
			}
			$buildList = array_merge($buildList, $this->getBuildList($repoItem['namespace'], $repoItem['name']));
		}

		return array_filter($buildList, function (Build $build) {
			return in_array($build->getStatus(),
				[self::BUILD_STATUS_PENDING, self::BUILD_STATUS_RUNNING]
			);
		});
	}

	/**
	 * @returns Build[]
	 * @throws RuntimeException
	 */
	protected function getBuildList(string $namespace, string $repo): array {
		$client = $this->httpClientService->newClient();

		$endpoint = sprintf(self::API_ENDPOINT_BUILDS, $namespace, $repo);
		try {
			$response = $client->get($this->configuration->getHost() . $endpoint, $this->getBaseHeaders());
			$buildList = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
		} catch (Exception $e) {
			throw new RuntimeException('Error while getting build list', $e->getCode(), $e);
		}
		$builds = [];
		foreach ($buildList as $buildItem) {
			$build = new Build();
			$build
				->setStatus($buildItem['status'])
				->setTitle($buildItem['title'] ?? $buildItem['message'])
				->setNumber((int)$buildItem['number'])
				->setEvent($buildItem['event'])
				->setNamespace($namespace)
				->setRepo($repo);

			$builds[] = $build;
		}
		return $builds;
	}
}
