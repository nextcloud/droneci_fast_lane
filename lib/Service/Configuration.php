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

use OCA\DroneciFastLane\AppInfo\Application;
use OCP\IConfig;
use RuntimeException;

class Configuration {
	private IConfig $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	public function getHost(): string {
		$host = $this->config->getAppValue(Application::APP_ID, 'host');
		$host = filter_var($host, FILTER_VALIDATE_URL);
		if ($host === false) {
			throw new RuntimeException('Invalid DroneCI host');
		}
		return rtrim($host, '/');
	}

	public function getToken(): string {
		$token = trim($this->config->getAppValue(Application::APP_ID, 'apitoken'));
		if ($token === '') {
			throw new RuntimeException('Drone API token not set');
		}
		return $token;
	}

	public function getRooms(): array {
		$roomList = trim($this->config->getAppValue(Application::APP_ID, 'rooms'));
		$rooms = explode(',', $roomList);
		return array_map('trim', $rooms);
	}
}
