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

use OCA\DroneciFastLane\Service\Prioritization;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildPrioritize extends \Symfony\Component\Console\Command\Command {

	private Prioritization $prioritization;

	public function __construct(Prioritization $prioritization) {
		parent::__construct();
		$this->prioritization = $prioritization;
	}

	protected function configure(): void {
		$this->setName('droneci:build:prioritize');
		$this->setDescription('Lists running and pending priorized builds on drone');

		$this->addArgument(
			'slug',
			InputArgument::REQUIRED,
			'The combination of repo and owner, e.g. nextcloud/server',
		);

		$this->addArgument(
			'build',
			InputArgument::REQUIRED,
			'The build number'
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		if (empty($input->getArgument('slug'))) {
			throw new \InvalidArgumentException('Slug must not be empty');
		}

		$build = (int)$input->getArgument('build');
		if ($build <= 0) {
			throw new \InvalidArgumentException('Build must not be 0 or lower');
		}

		if (!$this->prioritization->setPrioritized($input->getArgument(trim('slug')), $build)) {
			$output->writeln('<error>Could not prioritize this build</error>');
			return 1;
		}

		$this->prioritization->reorganizeQueue();

		return 0;
	}
}
