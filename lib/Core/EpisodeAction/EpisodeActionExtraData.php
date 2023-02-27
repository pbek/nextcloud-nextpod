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
    private int $fetchedAtUnix;

	public function __construct(
        ?string $episodeUrl,
		?string $podcastName,
        ?string $episodeName,
        ?string $episodeLink,
        ?string $episodeImage,
        int $fetchedAtUnix
	) {
		$this->episodeUrl = $episodeUrl;
        $this->podcastName = $podcastName;
		$this->episodeName = $episodeName;
		$this->episodeLink = $episodeLink;
		$this->episodeImage = $episodeImage;
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

        // Find episode by url and add data for it
        foreach($channel->item as $item)
        {
            $url = (string)$item->enclosure['url'];

            if ($url !== $episodeUrl) {
                continue;
            }

            $episodeName = self::stringOrNull($item->title);
            $episodeLink = self::stringOrNull($item->link);
//            $episodeImage = (string) $item->children('itunes', true)->image['href'];
            $episodeImageAttributes = (array) $item->children('http://www.itunes.com/dtds/podcast-1.0.dtd')->image->attributes();
            $episodeImage = array_key_exists('href', $episodeImageAttributes) ? (string) $episodeImageAttributes['href'] : '';
        }

        return new EpisodeActionExtraData(
            $episodeUrl,
            self::stringOrNull($channel->title),
            $episodeName,
            $episodeLink,
            $episodeImage,
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

