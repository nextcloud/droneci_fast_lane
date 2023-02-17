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

use InvalidArgumentException;
use OCA\DroneciFastLane\Service\Prioritization;
use OCA\DroneciFastLane\TalkCommand\Prioritize;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class PrioritizeTest extends TestCase {
	private Prioritization|MockObject $prioritizationService;
	private IL10N|MockObject $l10n;
	private Prioritize $command;

	public function setUp(): void {
		$this->prioritizationService = $this->createMock(Prioritization::class);
		$this->l10n = $this->createMock(IL10N::class);

		$this->l10n->expects($this->any())
			->method('t')
			->willReturnCallback(function (string $message, array $param) {
				return sprintf($message, ...$param);
			});

		$this->command = new Prioritize($this->prioritizationService, $this->l10n);
	}

	/**
	 * @dataProvider boolProvider
	 */
	public function testRun(bool $serviceResult): void {
		$this->prioritizationService->expects($this->once())
			->method('setPrioritized')
			->willReturn($serviceResult);

		$this->prioritizationService->expects($this->exactly($serviceResult ? 1 : 0))
			->method('reorganizeQueue');

		$output = $this->command->run(['nextcloud/server', '1234']);
		if ($serviceResult) {
			$this->assertStringContainsString('Prioritized build 1234 of nextcloud/server', $output);
		} else {
			$this->assertStringContainsString('Failed to prioritize build 1234 of nextcloud/server', $output);
		}
	}

	/**
	 * @dataProvider invalidArgumentsProvider
	 */
	public function testRunInvalidArgument(string $slug, string $number): void {
		$this->expectException(InvalidArgumentException::class);
		$this->command->run([$slug, $number]);
	}

	public function invalidArgumentsProvider(): array {
		return [
			['server', '1234'],
			['nextcloud/server', 'onetwothreefour'],
			['nextcloud/server', '-49'],
			['foo/bar/baz', '1234'],
			[' / ', '1234'],
		];
	}

	public function boolProvider(): array {
		return [[true], [false]];
	}
}
