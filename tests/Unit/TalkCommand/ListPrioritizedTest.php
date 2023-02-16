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

namespace OCA\DroneciFastLane\Tests\TalkCommand;

use Generator;
use OCA\DroneciFastLane\Entity\DroneBuild;
use OCA\DroneciFastLane\Service\Prioritization;
use OCA\DroneciFastLane\TalkCommand\ListPrioritized;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;
use function bin2hex;
use function random_bytes;

class ListPrioritizedTest extends TestCase {
	private Prioritization|MockObject $prioritizationService;
	private IL10N|MockObject $l10n;
	private ListPrioritized $command;

	public function setUp(): void {
		$this->prioritizationService = $this->createMock(Prioritization::class);
		$this->l10n = $this->createMock(IL10N::class);

		$this->l10n->expects($this->any())
			->method('t')
			->willReturnCallback(function (string $message, array $param) {
				return sprintf($message, ...$param);
			});

		$this->command = new ListPrioritized($this->prioritizationService, $this->l10n);
	}

	/**
	 * @dataProvider boolProvider
	 */
	public function testRun(bool $isQueueEmpty): void {
		$this->prioritizationService->expects($this->once())
			->method('getQueue')
			->willReturnCallback(function () use ($isQueueEmpty): Generator {
				if ($isQueueEmpty) {
					yield from [];
				} else {
					$builder = $this->getMockBuilder(DroneBuild::class)
						->addMethods(['getNumber', 'getTitle']);
					for ($i = 100; $i < 103; $i++) {
						$buildMock = $builder->getMock();
						$buildMock->expects($this->atLeastOnce())
							->method('getNumber')
							->willReturn($i);
						$buildMock->expects($this->atLeastOnce())
							->method('getTitle')
							->willReturn(bin2hex(random_bytes(5)));
						yield $buildMock;
					}
				}
			});

		$output = $this->command->run(['nextcloud/server', '1234']);
		if ($isQueueEmpty) {
			$this->assertStringContainsString('The priority queue is empty', $output);
		} else {
			$this->assertStringContainsString('Priority queue', $output);
		}
	}

	public function boolProvider(): array {
		return [[true], [false]];
	}
}
