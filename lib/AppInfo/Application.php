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

namespace OCA\DroneciFastLane\AppInfo;

use OCA\DroneciFastLane\Listener\TalkListener;
use OCA\Talk\Events\BeforeChatMessageSentEvent;
use OCP\App\IAppManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;

class Application extends App implements IBootstrap {
	public const APP_ID = 'droneci_fast_lane';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
	}

	public function boot(IBootContext $context): void {
		/** @var IAppManager $appManager */
		$appManager = $context->getServerContainer()->get(IAppManager::class);

		if ($appManager->isEnabledForUser('spreed')) {
			/** @var IEventDispatcher $dispatcher */
			$dispatcher = $context->getServerContainer()->get(IEventDispatcher::class);
			$dispatcher->addServiceListener(BeforeChatMessageSentEvent::class, TalkListener::class);
		}
	}
}
