# NextPod Nextcloud App

[GitHub](https://github.com/pbek/nextcloud-nextpod) |
[Nextcloud App Store](https://apps.nextcloud.com/apps/nextpod)

Initially implemented by [thrillfall](https://github.com/thrillfall) as [GPodderSync](https://github.com/thrillfall/nextcloud-gpodder),
then forked as [NextPod](https://github.com/pbek/nextcloud-nextpod) to add a user interface and more podcast client
functionality, which would be out of scope for a sync server.

This Nextcloud app that replicates basic gpodder.net api to sync podcast consumer apps (podcatchers) like AntennaPod.

## Screenhots

### Episode List

![episodes](./screenshots/episodes.png)

### Podcast Subscriptions

![podcasts](./screenshots/podcasts.png)

## Clients supporting sync

| client | support status |
| :- | :- |
| [AntennaPod](https://antennapod.org) | Initial purpose for this project, as a synchronization endpoint for this client.<br> Support is available [as of version 2.5.1](https://github.com/AntennaPod/AntennaPod/pull/5243/). |
| [KDE Kasts](https://apps.kde.org/de/kasts/) | Supported since version 21.12 |
| [Garmin Podcasts](https://lucasasselli.github.io/garmin-podcasts/) | Only for [compatible Garmin watches](https://apps.garmin.com/en-US/apps/b5b85600-0625-43b6-89e9-1245bd44532c), supported since version 3.3.4 |

## Installation

Either from the official Nextcloud app store ([link to app page](https://apps.nextcloud.com/apps/nextpod)) or by downloading the [latest release](https://github.com/pbek/nextcloud-nextpod/releases/latest) and extracting it into your Nextcloud apps/ directory.

## API

### subscription

* **get subscription changes**: `GET /index.php/apps/nextpod/subscriptions`
	* *(optional)* GET parameter `since` (UNIX time)
* **upload subscription changes** : `POST /index.php/apps/nextpod/subscription_change/create`
  * returns JSON with current timestamp

The API replicates this: https://gpoddernet.readthedocs.io/en/latest/api/reference/subscriptions.html

#### Example requests

- GET `/index.php/apps/nextpod/subscriptions?since=1633240761`

```json
{
  "add": [
    "https://example.com/feed.xml",
    "https://example.org/feed/"
  ],
  "remove": [
    "https://example.net/feed.rss"
  ],
  "timestamp": 1663540502
}
```

- POST `/index.php/apps/nextpod/subscription_change/create`

```json
{
  "add": [
    "https://example.com/feed.xml",
    "https://example.org/feed/"
  ],
  "remove": [
    "https://example.net/feed.rss"
  ]
}
```

### episode action

* **get episode actions**: `GET /index.php/apps/nextpod/episode_action`
	* *(optional)* GET parameter `since` (UNIX time)
	* fields: *podcast*, *episode*, *guid*, *action*, *timestamp*, *position*, *started*, *total*
* **create episode actions**: `POST /index.php/apps/nextpod/episode_action/create`
  * fields: *podcast*, *episode*, *guid*, *action*, *timestamp*, *position*, *started*, *total*
  * *position*, *started* and *total* are optional, default value is -1
  * *guid* is also optional, but should be sent if available
  * identification is done by *guid*, or *episode* if *guid* is missing
  * returns JSON with current timestamp

The API replicates this: https://nextpodnet.readthedocs.io/en/latest/api/reference/events.html  

#### Example requests

- GET `/index.php/apps/nextpod/episode_action?since=1633240761`

```json
{
    "actions": [
      {
       "podcast": "http://example.com/feed.rss",
       "episode": "http://example.com/files/s01e20.mp3",
       "guid": "s01e20-example-org",
       "action": "PLAY",
       "timestamp": "2009-12-12T09:00:00",
       "started": 15,
       "position": 120,
       "total":  500
      },
      {
       "podcast": "http://example.com/feed.rss",
       "episode": "http://example.com/files/s01e20.mp3",
       "guid": "s01e20-example-org",
       "action": "DOWNLOAD",
       "timestamp": "2009-12-12T09:00:00",
       "started": -1,
       "position": -1,
       "total":  -1
      },
    ],
    "timestamp": 12345
}
```

- POST `/index.php/apps/nextpod/episode_action/create`

```json
[
  {
   "podcast": "http://example.com/feed.rss",
   "episode": "http://example.com/files/s01e20.mp3",
   "guid": "s01e20-example-org",
   "action": "play",
   "timestamp": "2009-12-12T09:00:00",
   "started": 15,
   "position": 120,
   "total":  500
  },
  {
   "podcast": "http://example.org/podcast.php",
   "episode": "http://ftp.example.org/foo.ogg",
   "guid": "foo-bar-123",
   "action": "DOWNLOAD",
   "timestamp": "2009-12-12T09:05:21",
  }
]
```

## Development

### Testing

- mount project into apps-extra of nextcloud environment (https://github.com/juliushaertl/nextcloud-docker-dev) 
- `docker-compose exec nextcloud occ app:enable nextpod` enable app so we have database tables
- `docker-compose exec nextcloud phpunit9 -c apps-extra/nextcloud-nextpod/tests/phpunit.xml`
