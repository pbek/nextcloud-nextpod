<template>
	<NcListItem :title="getTitle()"
		:details="getDetails()">
		<template #icon>
			<NcAvatar :size="44"
				:url="getImageSrc()"
				:display-name="getAvatarName()" />
		</template>
		<template #subtitle>
			<span v-if="isLoading"><em>(Loading RSS data...)</em></span>
			<span v-else>{{ getSubtitle() }}</span>
		</template>
		<template #actions>
			<NcActionLink :href="getHomepageLink()"
				target="_blank"
				icon="icon-external">
				Podcast's homepage
			</NcActionLink>
			<NcActionLink :href="getRssLink()"
				target="_blank">
				<template #icon>
					<Rss />
				</template>
				RSS feed
			</NcActionLink>
		</template>
	</NcListItem>
</template>

<script>
import NcActionLink from '@nextcloud/vue/dist/Components/NcActionLink'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar'
import NcListItem from '@nextcloud/vue/dist/Components/NcListItem'

import Rss from 'vue-material-design-icons/Rss.vue'

import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

export default {
	name: 'SubscriptionListItem',
	components: {
		NcActionLink,
		NcAvatar,
		NcListItem,
		Rss,
	},
	props: {
		sub: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			podcastData: null,
			isLoading: true,
		}
	},
	async mounted() {
    await this.loadPodcastData();
	},
	methods: {
		async loadPodcastData() {
      try {
        const resp = await axios.get(generateUrl('/apps/nextpod/personal_settings/podcast_data?url={url}', {
          url: this.sub.url,
        }))
        this.podcastData = resp.data?.data
      } catch (e) {
        console.error(e)
      } finally {
        this.isLoading = false
      }
		},
		getTitle() {
			return this.podcastData?.title ?? this.sub.url ?? ''
		},
		getDetails() {
			if (this.sub.listenedSeconds <= 0) {
				return '(no time listened)'
			}
			const seconds = this.sub.listenedSeconds
			const hours = Math.floor(seconds / 3600)
			const modMinutes = Math.floor(seconds / 60) % 60
			if (hours === 0) {
				const modSeconds = seconds % 60
				return `(${modMinutes}min ${modSeconds}s listened)`
			}
			return `(${hours}h ${modMinutes}min listened)`
		},
		getImageSrc() {
			return this.podcastData?.imageBlob ?? this.podcastData?.imageUrl ?? ''
		},
		getAvatarName() {
			return this.podcastData?.author ?? ''
		},
		getSubtitle() {
			return this.podcastData?.description ?? ''
		},
		getHomepageLink() {
			return this.podcastData?.link ?? ''
		},
		getRssLink() {
			return this.sub.url ?? ''
		},
	},
  watch: {
    'sub.url'(val) {
      this.loadPodcastData();
    }
  }
}
</script>

<style lang="scss" scoped>
a.link {
	text-decoration: underline;
	color: var(--color-primary-element-light);
}
</style>
