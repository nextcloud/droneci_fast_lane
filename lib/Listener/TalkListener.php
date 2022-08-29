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

namespace OCA\DroneciFastLane\Listener;

use InvalidArgumentException;
use OCA\DroneciFastLane\Exception\CommandNotFound;
use OCA\DroneciFastLane\Service\Configuration;
use OCA\DroneciFastLane\TalkCommand\Mapper;
use OCA\Talk\Chat\ChatManager;
use OCA\Talk\Events\ChatParticipantEvent;
use OCA\Talk\Room;
use OCP\Server;

class TalkListener {
	private Configuration $configuration;

	public function __construct(Configuration $configuration) {
		$this->configuration = $configuration;
	}

	public static function handleCommand(ChatParticipantEvent $event) {
		$listener = Server::get(self::class);
		$listener->handle($event);
	}

	protected function isValidRoom(Room $room): bool {
		return in_array($room->getToken(), $this->configuration->getRooms());
	}

	public function handle(ChatParticipantEvent $event) {
		$message = $event->getComment();
		if (strpos($message->getMessage(), '!') !== 0) {
			return;
		}

		if (!$this->isValidRoom($event->getRoom())) {
			return;
		}

		$participant = $event->getParticipant();
		if (!$participant->hasModeratorPermissions()) {
			return;
		}

		$arguments = explode(' ', $message->getMessage());
		$command = array_shift($arguments);

		try {
			$commandClass = Mapper::find($command);
			$command = Server::get($commandClass);
			$output = $command->run($arguments);

			$message->setMessage($this->finalizeOutput($output, $event));
			$message->setActor('bots', 'DroneCI Fast Lane');
			$message->setVerb(ChatManager::VERB_COMMAND);
		} catch (CommandNotFound|InvalidArgumentException $e) {
			// eat it
			return;
		}
	}

	protected function finalizeOutput(string $output, ChatParticipantEvent $event): string {
		return \str_replace('{requester}', $event->getParticipant()->getAttendee()->getDisplayName(), $output);
	}
}
