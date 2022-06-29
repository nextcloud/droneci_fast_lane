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

namespace OCA\DroneciFastLane\Command;

use OCA\DroneciFastLane\Service\Drone;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListQueue extends Command {

	protected Drone $droneService;

	public function __construct(Drone $droneService) {
		parent::__construct();
		$this->droneService = $droneService;
	}

	protected function configure(): void {
		$this->setName('droneci:list:queue');
		$this->setDescription('Lists running and pending builds on drone');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$builds = $this->droneService->getBuildQueue();
		foreach ($builds as $build) {
			$lineBreak = strpos($build->getTitle(), "\n");
			$title = substr($build->getTitle(),0, min($lineBreak ?: 60, 60));
			if (strlen($title) < strlen($build->getTitle())) {
				$title .= '…';
			}
			$title = str_replace(["\r", "\n"], '', $title);

			printf("- %d\t%s\t%s\t%s\n\t%s\n", $build->getNumber(), $build->getEvent(), $build->getStatus(), $build->getRepo(), $title);
		}

		return 0;
	}

}
