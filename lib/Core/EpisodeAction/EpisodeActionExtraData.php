<?php
declare(strict_types=1);

namespace OCA\NextPod\Core\EpisodeAction;

use DateTime;
use Exception;
use JsonSerializable;
use OCA\NextPod\Core\PodcastData\PodcastData;
use SimpleXMLElement;

class EpisodeActionExtraData implements JsonSerializable {
	private ?string $podcastName;
	private ?string $episodeUrl;
	private ?string $episodeName;
	private ?string $episodeLink;
	private ?string $episodeImage;
	private ?string $episodeDescription;
    private int $fetchedAtUnix;

	public function __construct(
        ?string $episodeUrl,
		?string $podcastName,
        ?string $episodeName,
        ?string $episodeLink,
        ?string $episodeImage,
        ?string $episodeDescription,
        int $fetchedAtUnix
	) {
		$this->episodeUrl = $episodeUrl;
        $this->podcastName = $podcastName;
		$this->episodeName = $episodeName;
		$this->episodeLink = $episodeLink;
		$this->episodeImage = $episodeImage;
		$this->episodeDescription = $episodeDescription;
		$this->fetchedAtUnix = $fetchedAtUnix;
	}

	/**
	 * @return string|null
	 */
	public function getEpisodeUrl(): ?string {
		return $this->episodeUrl;
	}

	/**
	 * @return string
	 */
	public function __toString() : string {
		return $this->episodeUrl ?? '/no episodeUrl/';
	}

	/**
	 * @return array<string,mixed>
	 */
	public function toArray(): array {
		return
		[
			'podcastName' => $this->podcastName,
			'episodeUrl' => $this->episodeUrl,
			'episodeName' => $this->episodeName,
			'episodeLink' => $this->episodeLink,
			'episodeImage' => $this->episodeImage,
			'episodeDescription' => $this->episodeDescription,
			'fetchedAtUnix' => $this->fetchedAtUnix,
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	public function jsonSerialize(): array {
		return $this->toArray();
	}

	/**
	 * @return EpisodeActionExtraData
	 */
	public static function fromArray(array $data): EpisodeActionExtraData {
		return new EpisodeActionExtraData(
            $data['episodeUrl'],
			$data['podcastName'],
			$data['episodeName'],
			$data['episodeLink'],
			$data['episodeImage'],
			$data['episodeDescription'],
			$data['fetchedAtUnix']
		);
	}

    /**
     * @return string|null
     */
    public function getPodcastName(): ?string
    {
        return $this->podcastName;
    }

    /**
     * @return string|null
     */
    public function getEpisodeName(): ?string
    {
        return $this->episodeName;
    }

    /**
     * @return string|null
     */
    public function getEpisodeLink(): ?string
    {
        return $this->episodeLink;
    }

    /**
     * @return PodcastData
     * @throws Exception if the XML data could not be parsed.
     */
    public static function parseRssXml(string $xmlString, string $episodeUrl, ?int $fetchedAtUnix = null): EpisodeActionExtraData {
        $xml = new SimpleXMLElement($xmlString);
        $channel = $xml->channel;
        $episodeName = null;
        $episodeLink = null;
        $episodeImage = null;
        $episodeDescription = null;
        $episodeUrlPath = parse_url($episodeUrl, PHP_URL_PATH);

        // Find episode by url and add data for it
        foreach($channel->item as $item)
        {
            $url = (string)$item->enclosure['url'];

            // First try to match the url directly
            if (strpos($episodeUrl, $url) === false) {
                // Then try to match the path only
                // The podcast http://feeds.feedburner.com/abcradio/10percenthappier has a "rss_browser" query parameter
                // for every item that changed all the time, so we can't match the full url
                $path = parse_url($url, PHP_URL_PATH);

                if ($episodeUrlPath !== $path) {
                    continue;
                }
            }

            // Get episode name
            $episodeName = self::stringOrNull($item->title);

            // Get episode link
            $episodeLink = self::stringOrNull($item->link);

            //
            // Get episode image
            //

            $episodeImageAttributes = (array) $item->children('http://www.itunes.com/dtds/podcast-1.0.dtd')->image->attributes();
            $episodeImage = self::stringOrNull(array_key_exists('href', $episodeImageAttributes) ? (string) $episodeImageAttributes['href'] : '');

            if (!$episodeImage) {
                $episodeImage = self::stringOrNull((string) $item->children('itunes', true)->image['href']);
            }

            if (!$episodeImage) {
                $episodeImage = self::stringOrNull($channel->image->url);
            }

            if (!$episodeImage) {
                $episodeImage = self::stringOrNull((string) $channel->children('itunes', true)->image['href']);
            }

            if (!$episodeImage) {
                $episodeImageAttributes = (array) $channel->children('http://www.itunes.com/dtds/podcast-1.0.dtd')->image->attributes();
                $episodeImage = self::stringOrNull(array_key_exists('href', $episodeImageAttributes) ? (string) $episodeImageAttributes['href'] : '');
            }

            if (!$episodeImage) {
                preg_match('/<itunes:image\s+href="([^"]+)"/', $xmlString, $matches);
                $episodeImage = self::stringOrNull($matches[1]);
            }

            //
            // Get episode description
            //

            $episodeDescription = self::stringOrNull($item->children('content', true)->encoded);

            if (!$episodeDescription) {
                $episodeDescription = self::stringOrNull($item->description);
            }

            // Open links in new browser window/tab
            $episodeDescription = str_replace('<a ', '<a class="description-link" target="_blank" ', $episodeDescription);

            break;
        }

        return new EpisodeActionExtraData(
            $episodeUrl,
            self::stringOrNull($channel->title),
            $episodeName,
            $episodeLink,
            $episodeImage,
            $episodeDescription,
            $fetchedAtUnix ?? (new DateTime())->getTimestamp()
        );
    }

    private static function stringOrNull($value): ?string {
        if ($value) {
            return (string)$value;
        }
        return null;
    }

    /**
     * @return int
     */
    public function getFetchedAtUnix(): int
    {
        return $this->fetchedAtUnix;
    }

    /**
     * @return string|null
     */
    public function getEpisodeImage(): ?string
    {
        return $this->episodeImage;
    }
}

