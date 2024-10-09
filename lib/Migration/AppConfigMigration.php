<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Arthur Schiwon <blizzz@arthur-schiwon.de>
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

namespace OCA\DroneciFastLane\Migration;

use OCA\DroneciFastLane\Service\Configuration;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class AppConfigMigration implements IRepairStep {
	public function __construct(
		private Configuration $config,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'Update configuration metadata';
	}

	/**
	 * @inheritDoc
	 */
	public function run(IOutput $output): void {
		$this->config->configure();
	}
}
