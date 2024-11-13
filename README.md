# DroneCI Fast Lane

**⚠ This app is discontinued and unmaintained ⚠**

DroneCI is a continuous integration tool, to run test cases after events on the version control system. When, for example, a pull request was created, a build is queued on the DroneCI server. DroneCI uses a simple FIFO queues: the oldest item in the list will be handled next.

Sometimes however, when there are a few items in the queue, one (or a few) specific builds should be handled with priority. While DroneCI does not offer a feature, it is possible to cancel and restart builds, that are in front of the target build. By restarting them, they will be appended to the queue.

And this is what the app does. Via `occ` or `Talk` interface, builds can be flagged priority, and every non-started build upfront will be send to the end of the queue.

## Configuration

The app can be configured via `occ` only:

### Drone Host

`php occ config:app:set --value="https://drone.example.com" droneci_fast_lane host`

### Drone API Token

`php occ config:app:set --value="meiMahT3vMyDroneApiTokenel7eqi8S" droneci_fast_lane apitoken`

### Talk rooms

You can set just one room by specifying the room token. It can be copied from the URL, for example allowing commands from the room at https://cloud.example.com/call/Thun8Uu0:

`php occ config:app:set --value="Thun8Uu0" droneci_fast_lane rooms`

More rooms can be allowed through comma-separation:

`php occ config:app:set --value="Thun8Uu0,5650832712" droneci_fast_lane rooms`

## Usage

### occ

#### List build queue

Lists all builds that are started or pending

`php occ droneci:list:queue`

#### List prioritized builds

Lists all builds that were flagged as priority

`php occ droneci:list:prioritized`

#### Prioritize a build

Mark the build identified by slug and build number as prioritized and re-arrange the build queue.

```
php occ droneci:build:prioritize $SLUG $BUILDNO

php occ droneci:build:prioritize example/project 3457
```

### Talk

At least one talk room has to be configured from which sending commands to this app is allowed. Commands are accepted from moderators only. Commands are submitted as chat messages.

#### Show help

```
!h, !help
```

#### List build queue

Lists all builds that are started or pending

`!lq, !list-queue`

#### List prioritized builds

Lists all builds that were flagged as priority

`!lp, !list-prio`


#### Prioritize a build

Mark the build identified by slug and build number as prioritized and re-arrange the build queue.

```
!p, !prio $SLUG $BUILDNO

!prio example/project 3457
```

## Development

Clone this git repository and start hacking. 

It does not have frontend bits of its own, so there are no frontend/JS framework or dependencies.

Currently, there are no PHP dependencies either, but development ones that can be installed via `composer i`.
