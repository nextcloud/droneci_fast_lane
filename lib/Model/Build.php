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

namespace OCA\DroneciFastLane\Model;

class Build {
	private int $number;
	private string $status;
	private string $title;
	private string $event;
	private string $namespace;
	private string $repo;

	public function getStatus(): string {
		return $this->status;
	}

	public function setStatus(string $status): Build {
		$this->status = $status;
		return $this;
	}

	public function setTitle(string $title): Build {
		$this->title = $title;
		return $this;
	}

	public function getTitle(): string {
		return $this->title;
	}

	public function getNumber(): int {
		return $this->number;
	}

	public function setNumber(int $number): Build {
		$this->number = $number;
		return $this;
	}

	public function setEvent(string $event): Build {
		$this->event = $event;
		return $this;
	}

	public function getEvent(): string {
		return $this->event;
	}

	public function getNamespace(): string {
		return $this->namespace;
	}

	public function setNamespace(string $namespace): Build {
		$this->namespace = $namespace;
		return $this;
	}

	public function getRepo(): string {
		return $this->repo;
	}

	public function setRepo(string $repo): Build {
		$this->repo = $repo;
		return $this;
	}

}
