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

use OCA\DroneciFastLane\Exception\CommandNotFound;
use Psr\Container\ContainerInterface;

class Locator {
	public const MAP = [
		'!help' => Help::class,
		'!h' => Help::class,
		'!prio' => Prioritize::class,
		'!p' => Prioritize::class,
		'!list-queue' => ListQueue::class,
		'!lq' => ListQueue::class,
		'!list-prio' => ListPrioritized::class,
		'!lp' => ListPrioritized::class,
	];
	private ContainerInterface $server;

	public function __construct(ContainerInterface $server) {
		$this->server = $server;
	}

	/**
	 * @throws CommandNotFound
	 */
	public function get(string $command): ICommand {
		if (isset(self::MAP[$command])) {
			return $this->server->get(self::MAP[$command]);
		}
		throw new CommandNotFound();
	}
}
