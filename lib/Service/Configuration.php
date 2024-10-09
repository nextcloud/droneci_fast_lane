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
use OCP\IAppConfig;
use RuntimeException;

class Configuration {

	public function __construct(
		private IAppConfig $config,
	) {
	}

	public function getHost(): string {
		$host = $this->config->getValueString(Application::APP_ID, 'host', '', true);
		$host = filter_var($host, FILTER_VALIDATE_URL);
		if ($host === false) {
			throw new RuntimeException('Invalid DroneCI host');
		}
		return rtrim($host, '/');
	}

	public function getToken(): string {
		$token = trim($this->config->getValueString(Application::APP_ID, 'apitoken', '', true));
		if ($token === '') {
			throw new RuntimeException('Drone API token not set');
		}
		return $token;
	}

	public function getRooms(): array {
		$roomList = trim($this->config->getValueString(Application::APP_ID, 'rooms', '', true));
		$rooms = explode(',', $roomList);
		return array_map('trim', $rooms);
	}

	public function configure(): void {
		$this->config->updateLazy(Application::APP_ID, 'host', true);
		$this->config->updateLazy(Application::APP_ID, 'rooms', true);
		$this->config->updateLazy(Application::APP_ID, 'apitoken', true);
		$this->config->updateSensitive(Application::APP_ID, 'apitoken', true);
	}
}
