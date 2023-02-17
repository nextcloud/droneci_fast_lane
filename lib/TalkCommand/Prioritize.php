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

use InvalidArgumentException;
use OCA\DroneciFastLane\Service\Prioritization;
use OCP\IL10N;
use function explode;

class Prioritize implements ICommand {
	private Prioritization $prioritization;
	private IL10N $l10n;

	public function __construct(Prioritization $prioritization, IL10N $l10n) {
		$this->prioritization = $prioritization;
		$this->l10n = $l10n;
	}

	/**
	 * @param string[] $arguments
	 * @throws InvalidArgumentException
	 */
	public function run(array $arguments): string {
		$slug = $this->qualifySlug(array_shift($arguments));
		$build = $this->qualifyBuildNumber(array_shift($arguments));
		$status = $this->prioritization->setPrioritized($slug, $build);
		if ($status) {
			$this->prioritization->reorganizeQueue();
		}

		return $this->prepareOutput($status, $slug, $build);
	}

	public function help(): string {
		return $this->l10n->t('⏩ Prioritize a build:') . PHP_EOL . '!p, !prio $namespace/$repo $build' . PHP_EOL;
	}

	protected function prepareOutput(bool $success, string $slug, int $build): string {
		if ($success) {
			return $this->l10n->t('✔️ Prioritized build %d of %s as requested by %s.', [$build, $slug, '{requester}']);
		}
		return $this->l10n->t('❌ Failed to prioritize build %d of %s as requested by %s.', [$build, $slug, '{requester}']);
	}

	/**
	 * @throws InvalidArgumentException
	 */
	protected function qualifySlug(string $candidate): string {
		$parts = explode('/', $candidate);
		$parts = array_map('trim', $parts);
		$parts = array_filter($parts, function (string $item) {
			return $item !== '';
		});
		if (count($parts) === 2) {
			return implode('/', $parts);
		}
		throw new InvalidArgumentException('Invalid slug provided');
	}

	/**
	 * @throws InvalidArgumentException
	 */
	protected function qualifyBuildNumber(string $candidate): int {
		$build = (int)$candidate;
		if ($build > 1) {
			return $build;
		}
		throw new InvalidArgumentException('Invalid build number provided');
	}
}
