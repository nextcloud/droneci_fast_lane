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

namespace OCA\DroneciFastLane\TalkCommand;

use OCA\DroneciFastLane\Service\Drone;
use OCP\IL10N;

class ListQueue implements ICommand {
	private Drone $droneService;
	private IL10N $l10n;

	public function __construct(Drone $droneService, IL10N $l10n) {
		$this->droneService = $droneService;
		$this->l10n = $l10n;
	}

	/**
	 * @param string[] $arguments
	 */
	public function run(array $arguments): string {
		$output = '';
		$currentRepo = '';
		foreach ($this->droneService->getBuildQueue() as $build) {
			if ($currentRepo !== $build->getSlug()) {
				$currentRepo = $build->getSlug();
				$output .= PHP_EOL . '🗃️ ' . $build->getSlug() . PHP_EOL;
			}

			$statusIcon = $build->getStatus() === Drone::BUILD_STATUS_PENDING ? '⏳' : '🏗️';
			$output .= \sprintf("\t%s %d %s", $statusIcon, $build->getNumber(), $this->formatTitle($build->getTitle())) . PHP_EOL;
		}

		if ($output !== '') {
			$output = $this->l10n->t('📋 Build queue by repository' . PHP_EOL) . $output;
		} else {
			$output = $this->l10n->t('📋 The build queue is empty') ;
		}

		return $output;
	}

	public function help(): string {
		return $this->l10n->t('📋 List the build queue:') . PHP_EOL . '!lq, !list-queue' . PHP_EOL;
	}

	protected function formatTitle(string $title): string {
		$lineBreak = strpos($title, "\n");
		if ($lineBreak !== false) {
			$title = substr($title, 0, $lineBreak) . '…';
		}
		return trim($title);
	}
}
