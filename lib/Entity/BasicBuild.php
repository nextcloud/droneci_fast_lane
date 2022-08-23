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

namespace OCA\DroneciFastLane\Entity;

use OCP\AppFramework\Db\Entity;

/**
 * @method void setNumber(int $getNumber)
 * @method void setNamespace(string $getNamespace)
 * @method void setRepo(string $getRepo)
 * @method void setCreatedAt(int $getCreatedAt)
 * @method int getNumber()
 * @method string getNamespace()
 * @method string getRepo()
 * @method int getCreatedAt()
 */
class BasicBuild extends Entity {
	protected int $number = -1;
	protected int $createdAt = 0;
	protected string $namespace = '';
	protected string $repo = '';

	public function __construct() {
		$this->addType('number', 'int');
		$this->addType('namespace', 'string');
		$this->addType('repo', 'string');
		$this->addType('createdAt', 'int');
	}

	public function uniqueName(): string {
		return $this->getSlug() . '/' . $this->getNumber();
	}

	public static function fromDerivedBuild(BasicBuild $build): BasicBuild {
		$prioritizedBuild = new BasicBuild();
		$prioritizedBuild->setNumber($build->getNumber());
		$prioritizedBuild->setNamespace($build->getNamespace());
		$prioritizedBuild->setRepo($build->getRepo());
		$prioritizedBuild->setCreatedAt($build->getCreatedAt());
		return $prioritizedBuild;
	}

	// FIXME: slug may differ from $namespace/$repo depending on used characters. Theoretical problem for us.
	public function getSlug(): string {
		return $this->getNamespace() . '/' . $this->getRepo();
	}
}
