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

namespace OCA\DroneciFastLane\Tests\Unit\Service;

use OCA\DroneciFastLane\Db\PriorityMapper;
use OCA\DroneciFastLane\Entity\BasicBuild;
use OCA\DroneciFastLane\Entity\DroneBuild;
use OCA\DroneciFastLane\Service\Drone;
use OCA\DroneciFastLane\Service\Prioritization;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class PrioritizationTest extends TestCase {
	protected Prioritization $prioritizationService;
	/** @var PriorityMapper|MockObject */
	private $mapper;
	/** @var Drone|MockObject */
	private $drone;

	public function setUp(): void {
		$this->mapper = $this->createMock(PriorityMapper::class);
		$this->drone = $this->createMock(Drone::class);
		$logger = $this->createMock(LoggerInterface::class);

		$this->prioritizationService = new Prioritization($this->mapper, $this->drone, $logger);
	}

	public static function tearDownAfterClass(): void {
	}

	public function buildIdentifierProvider(): array {
		return [
			['nextcloud/server', 123, false, true],
			['nextcloud/server', 123, true, false],
			['nextcloud/server', 0, false, false],
			['nextcloud', 123, false, false],
			['', 123, false, false],
		];
	}

	/**
	 * @dataProvider buildIdentifierProvider
	 */
	public function testSetPrioritized(string $slug, int $number, bool $alreadyPrioritized, bool $expectedResult) {
		$ctime = time() - 333;
		$prioritizedBuilds = [];
		if ($alreadyPrioritized) {
			$build = new BasicBuild();
			$build->setNamespace('nextcloud');
			$build->setCreatedAt(time() - 321);
			$build->setNumber(234);
			$build->setRepo('server');
			$prioritizedBuilds[] = $build;
		}

		$this->mapper->expects($this->once())
			->method('getBuilds')
			->willReturn($prioritizedBuilds);
		$this->mapper->expects($this->exactly($expectedResult ? 1 : 0))
			->method('insert');

		$droneMocker = $this->drone->expects($this->any())
			->method('getBuildInfo');
		if ($expectedResult) {
			$droneBuild = new DroneBuild();
			$droneBuild->setNumber($number);
			$droneBuild->setCreatedAt($ctime);
			$droneBuild->setNamespace('nextcloud');
			$droneBuild->setRepo('server');
			$droneMocker->willReturn($droneBuild);
		} else {
			$droneMocker->willThrowException(new \RuntimeException('kaput'));
		}

		$result = $this->prioritizationService->setPrioritized($slug, $number);
		$this->assertSame($expectedResult, $result);
	}

	public function makeBasicBuild(): BasicBuild {
		$build = new BasicBuild();
		$build->setNamespace('nextcloud');
		$build->setRepo('server');
		$no = \random_int(1, 99999);
		$build->setNumber($no);
		$build->setCreatedAt(time() - (99999 - $no));
		return $build;
	}

	public function makeDroneBuild(?int $no = null): DroneBuild {
		$build = new DroneBuild();
		$build->setNamespace('nextcloud');
		$build->setRepo('server');
		$no = $no ?? \random_int(1, 99999);
		$build->setNumber($no);
		$build->setCreatedAt(time() - (99999 - $no));
		$build->setStatus(Drone::BUILD_STATUS_PENDING);
		return $build;
	}

	public function testGetQueue(): void {
		$buildList = [
			$this->makeBasicBuild(),
			$this->makeBasicBuild(),
			$this->makeBasicBuild(),
		];

		$statusToReturn = [
			Drone::BUILD_STATUS_RUNNING,
			'success',
			Drone::BUILD_STATUS_PENDING,
		];

		$this->drone->expects($this->exactly(3))
			->method('getBuildInfo')
			->willReturnCallback(function () use (&$statusToReturn) {
				$build = $this->makeDroneBuild();
				$build->setStatus(array_pop($statusToReturn));
				return $build;
			});

		$this->mapper->expects($this->once())
			->method('getBuilds')
			->willReturn($buildList);

		$expectedBuilds = 2;
		$returnedBuilds = 0;
		$generator = $this->prioritizationService->getQueue();
		foreach ($generator as $buildItem) {
			$returnedBuilds++;
		}
		// output is expected to contain only pending and running builds
		$this->assertSame($expectedBuilds, $returnedBuilds);
	}

	public function testReorganizeQueue(): void {
		$droneBuildList = [
			$this->makeDroneBuild(1200),  // leave as running
			$this->makeDroneBuild(1201),  // restart
			$this->makeDroneBuild(1202),  // restart
			$this->makeDroneBuild(1203),  // prio
			$this->makeDroneBuild(1205),  // leave
		];
		$droneBuildList[0]->setStatus(Drone::BUILD_STATUS_RUNNING);

		$prioBuildList = [BasicBuild::fromDerivedBuild($droneBuildList[3])];

		$this->mapper->expects($this->once())
			->method('getBuilds')
			->willReturn($prioBuildList);

		$this->drone->expects($this->once())
			->method('getBuildQueue')
			->willReturnCallback(function () use ($droneBuildList) {
				foreach ($droneBuildList as $build) {
					yield $build->uniqueName() => $build;
				}
			});

		$this->drone->expects($this->exactly(2))
			->method('restartBuild');

		$this->prioritizationService->reorganizeQueue();
	}

	/**
	 * @return BasicBuild|MockObject
	 */
	protected function getPrioritizedBuildMock(int $number) {
		$build = $this->createMock(BasicBuild::class);
		$build->expects($this->any())
			->method('getNumber')
			->willReturn($number);
		$build->expects($this->any())
			->method('getNamespace')
			->willReturn('nextcloud');
		$build->expects($this->any())
			->method('getRepo')
			->willReturn('server');

		return $build;
	}
}
