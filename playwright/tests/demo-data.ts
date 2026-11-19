import type { Page } from '@playwright/test';

const podcasts = [
  {
    id: 'field-notes',
    title: 'Field Notes',
    description: 'Stories about the natural world, recorded where they happen.',
    colors: ['#123f36', '#9de3a4'],
    artwork: '<path d="M12 46 29 22l9 12 8-10 16 22Z"/><circle cx="48" cy="14" r="4"/>',
  },
  {
    id: 'after-hours',
    title: 'After Hours Astronomy',
    description: 'A friendly guide to the night sky.',
    colors: ['#25295e', '#bbb7ff'],
    artwork: '<circle cx="36" cy="33" r="14"/><ellipse cx="36" cy="33" rx="27" ry="7" transform="rotate(-25 36 33)"/>',
  },
  {
    id: 'side-streets',
    title: 'Side Streets',
    description: 'What our neighborhoods can teach us about cities.',
    colors: ['#8c3624', '#ffd59c'],
    artwork: '<path d="M13 49V33l13-11 10 8 11-13 12 10v22ZM26 22v27m21-32v32"/>',
  },
  {
    id: 'small-batch',
    title: 'Small Batch',
    description: 'Kitchen experiments worth repeating.',
    colors: ['#744060', '#ffd3de'],
    artwork: '<path d="M15 35h42a21 17 0 0 1-42 0Zm11-7q-6-6 0-12m10 12q-6-6 0-12m10 12q-6-6 0-12"/>',
  },
  {
    id: 'signal-flow',
    title: 'Signal Flow',
    description: 'How everyday sounds are made, recorded, and remembered.',
    colors: ['#1c4a67', '#a9e3ff'],
    artwork: '<path d="M10 36h10l5-12 8 23 8-30 8 22 5-8h8"/>',
  },
  {
    id: 'paper-trails',
    title: 'Paper Trails',
    description: 'The unexpected history of ordinary things.',
    colors: ['#654713', '#ffe29a'],
    artwork: '<path d="M36 24q-12-8-23-4v25q11-4 23 4 12-8 23-4V20q-11-4-23 4Zm0 0v25"/>',
  },
];

export const featuredTitle = 'The Secret Life of City Trees';
export const featuredPosition = 8 * 60 + 24;
const featuredDuration = 24 * 60;
const episodes = [
  { podcast: 0, title: featuredTitle, position: featuredPosition, total: featuredDuration },
  { podcast: 1, title: 'A Beginner’s Guide to the Moon', position: 1860, total: 1860 },
  { podcast: 2, title: 'The Case for Walkable Blocks', position: 960, total: 2400 },
  { podcast: 0, title: 'Listening for the First Birds of Spring', position: 2175, total: 2175 },
  { podcast: 4, title: 'How a Room Changes a Recording', position: 685, total: 1790 },
  { podcast: 3, title: 'A Better Loaf of Bread', position: 1920, total: 1920 },
  { podcast: 1, title: 'Finding Saturn from Your Backyard', position: 1250, total: 1680 },
  { podcast: 2, title: 'Who Gets to Name a Street?', position: 1960, total: 1960 },
  { podcast: 0, title: 'When Rivers Make Their Own Rules', position: 825, total: 2025 },
  { podcast: 3, title: 'The Quiet Art of Making Stock', position: 992, total: 1415 },
  { podcast: 4, title: 'What Makes a Good Microphone?', position: 1145, total: 1740 },
  { podcast: 0, title: 'A Night Walk Through the Wetlands', position: 325, total: 2430 },
  { podcast: 5, title: 'Why Postcards Still Matter' },
  { podcast: 3, title: 'The Citrus Season' },
  { podcast: 1, title: 'How to Watch a Meteor Shower' },
  { podcast: 4, title: 'The Sound of an Empty Theater' },
  { podcast: 2, title: 'What Happens After the Last Train?' },
];

const featuredDescription = `
  <p>On an ordinary city block, a row of trees cools the sidewalk, catches rain and makes room for wildlife.
  We take a walk with an urban ecologist to find out how much happens beneath the canopy.</p>
  <h3>In this episode</h3>
  <ul>
    <li><strong>03:10</strong> — How street trees cool a neighborhood</li>
    <li><strong>08:24</strong> — What happens when roots meet pavement</li>
    <li><strong>16:45</strong> — Choosing trees that can thrive in a changing climate</li>
  </ul>
  <p>Try it on your next walk: compare the temperature on a shaded street and a sunny one.</p>`;

// An 8-bit PCM WAV with no recorded audio. Its duration matches the demo progress in the player.
function silentAudio(seconds: number) {
  const sampleRate = 8000;
  const samples = seconds * sampleRate;
  const wav = Buffer.alloc(44 + samples, 128);
  wav.write('RIFF', 0);
  wav.writeUInt32LE(wav.length - 8, 4);
  wav.write('WAVEfmt ', 8);
  wav.writeUInt32LE(16, 16);
  wav.writeUInt16LE(1, 20); // PCM
  wav.writeUInt16LE(1, 22); // Mono
  wav.writeUInt32LE(sampleRate, 24);
  wav.writeUInt32LE(sampleRate, 28);
  wav.writeUInt16LE(1, 32); // Bytes per sample frame
  wav.writeUInt16LE(8, 34);
  wav.write('data', 36);
  wav.writeUInt32LE(samples, 40);
  return wav;
}

export async function useDemoData(page: Page, baseURL: string) {
  const demoURL = new URL('/nextpod-screenshot-demo/', baseURL).href;
  const shows = podcasts.map((podcast) => ({
    ...podcast,
    feed: `${demoURL}${podcast.id}/feed.xml`,
    image: `${demoURL}${podcast.id}/cover.svg`,
    link: `https://example.com/podcasts/${podcast.id}`,
  }));
  const actions = episodes.map(({ podcast, position, total }, index) => ({
    podcastUrl: shows[podcast].feed,
    episodeUrl: `${demoURL}audio/${index + 1}.wav`,
    guid: `demo-${index + 1}`,
    action: position === undefined ? 'DOWNLOAD' : 'PLAY',
    started: position === undefined ? -1 : 0,
    position: position ?? -1,
    total: total ?? -1,
  }));
  const subscriptions = shows.map(({ feed }) => ({
    url: feed,
    listenedSeconds: actions
      .filter(({ podcastUrl, position }) => podcastUrl === feed && position >= 0)
      .reduce((sum, { position }) => sum + position, 0),
  })).sort((a, b) => b.listenedSeconds - a.listenedSeconds);

  const episodeData = new Map(actions.map(({ episodeUrl }, index) => {
    const { podcast, title } = episodes[index];
    const show = shows[podcast];
    return [episodeUrl, {
      episodeName: title,
      podcastName: show.title,
      episodeImage: show.image,
      episodeLink: `${show.link}/${index + 1}`,
      episodeDescription: index === 0 ? featuredDescription : `<p>${show.description}</p>`,
    }];
  }));
  const podcastData = new Map(shows.map(({ feed, title, description, image, link }) => [
    feed, { title, description, imageUrl: image, link },
  ]));
  const covers = new Map(shows.map(({ image, colors: [background, foreground], artwork }) => [
    image,
    `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 72 72">
       <rect width="72" height="72" fill="${background}"/>
       <g fill="none" stroke="${foreground}" stroke-width="3" stroke-linejoin="round"
          stroke-linecap="round" transform="translate(0 3)">${artwork}</g>
     </svg>`,
  ]));
  const audio = silentAudio(featuredDuration);

  const origin = new URL(baseURL).origin;
  await page.route((url) => url.origin === origin &&
    /\/apps\/nextpod\/personal_settings\/(metrics|action_extra_data|podcast_data)$/.test(url.pathname), async (route) => {
    const url = new URL(route.request().url());
    if (url.pathname.endsWith('/metrics')) {
      await route.fulfill({ json: { actions, subscriptions } });
      return;
    }

    const data = url.pathname.endsWith('/action_extra_data')
      ? episodeData.get(url.searchParams.get('episodeUrl') ?? '')
      : podcastData.get(url.searchParams.get('url') ?? '');
    await route.fulfill(data ? { json: { data } } : { status: 404 });
  });

  await page.route(`${demoURL}**`, async (route) => {
    const url = route.request().url();
    const cover = covers.get(url);
    if (cover) {
      await route.fulfill({ contentType: 'image/svg+xml', body: cover });
    } else if (url === actions[0].episodeUrl) {
      // Chromium requests byte ranges when seeking to the saved play position.
      const headers: Record<string, string> = { 'accept-ranges': 'bytes' };
      const range = route.request().headers().range?.match(/^bytes=(\d+)-(\d*)$/);
      if (range) {
        const start = Number(range[1]);
        const end = range[2] ? Math.min(Number(range[2]), audio.length - 1) : audio.length - 1;
        if (start > end) {
          await route.fulfill({ status: 416, headers: { 'content-range': `bytes */${audio.length}` } });
          return;
        }
        headers['content-range'] = `bytes ${start}-${end}/${audio.length}`;
        await route.fulfill({
          status: 206, contentType: 'audio/wav', headers, body: audio.subarray(start, end + 1),
        });
      } else {
        await route.fulfill({ contentType: 'audio/wav', headers, body: audio });
      }
    } else {
      await route.fulfill({ status: 404 });
    }
  });
}
