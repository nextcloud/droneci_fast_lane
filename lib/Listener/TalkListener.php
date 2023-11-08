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
use OCA\DroneciFastLane\TalkCommand\Locator;
use OCA\Talk\Chat\ChatManager;
use OCA\Talk\Events\BeforeChatMessageSentEvent;
use OCA\Talk\Room;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use function str_replace;

class TalkListener implements IEventListener {

	public function __construct(
		private Configuration $configuration,
		private Locator $locator,
	) {
	}

	protected function isValidRoom(Room $room): bool {
		return in_array($room->getToken(), $this->configuration->getRooms());
	}

	public function handle(Event $event): void {
		if (!$event instanceof BeforeChatMessageSentEvent) {
			return;
		}

		$message = $event->getComment();
		if (!str_starts_with($message->getMessage(), '!')) {
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
			$command = $this->locator->get($command);
			$output = $command->run($arguments);

			$message->setMessage($this->finalizeOutput($output, $event));
			$message->setActor('bots', 'DroneCI Fast Lane');
			$message->setVerb(ChatManager::VERB_COMMAND);
		} catch (CommandNotFound|InvalidArgumentException) {
			// eat it
			return;
		}
	}

	protected function finalizeOutput(string $output, BeforeChatMessageSentEvent $event): string {
		return str_replace('{requester}', $event->getParticipant()->getAttendee()->getDisplayName(), $output);
	}
}
