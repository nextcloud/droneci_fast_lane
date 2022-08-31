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

use OCP\IL10N;

class Help implements ICommand {
	private IL10N $l10n;
	private Locator $locator;

	public function __construct(IL10N $l10n, Locator $locator) {
		$this->l10n = $l10n;
		$this->locator = $locator;
	}

	public function run(array $arguments): string {
		$classes = array_unique($this->locator::MAP);

		$output = '⛑️ DroneCI Fast Lane Helpdesk' . PHP_EOL . PHP_EOL;
		foreach ($classes as $commandHandle => $commandClass) {
			if ($commandClass === self::class) {
				continue;
			}
			$command = $this->locator->get($commandHandle);
			$output .= $command->help() . PHP_EOL;
			unset($command);
		}
		$output .= $this->l10n->t('ℹ️ Show this help:') . PHP_EOL . '!h, !help';

		return $output;
	}

	public function help(): string {
		// NOOP
		return '';
	}
}
