<template>
	<NcListItem :title="isLoading ? action.episodeUrl : getEpisodeName()"
              @click="showModalEpisodeDescription"
            :details="getDetails()">
    <template #icon>
      <!--
      <img v-if="getImageSrc() !== ''" :alt="getEpisodeName()" :src="getImageSrc()" />
      <NcAvatar v-if="getImageSrc() === ''"
              :size="44"
              :url="getImageSrc()"
              :display-name="getEpisodeName()" />
      -->
      <NcAvatar :size="44"
              :url="getImageSrc()"
              :display-name="getEpisodeName()" />
    </template>
    <template #subtitle>
      <span v-if="isLoading"><em>(Loading RSS data...)</em></span>
      <span v-else>{{ getPodcastName() }}</span>
      <NcModal
          v-if="modalPlayer"
          @close="closeModalPlayer"
          size="normal"
          title="Play media"
          :outTransition="true">
        <div class="modal__content">
          <h2 v-if="isLoading">Playing episode"</h2>
          <h2 v-else>Playing "{{ getEpisodeName() }}"</h2>
          <audio controls autoplay>
            <source :src="action.episodeUrl">
            Your browser does not support the audio element.
          </audio>
        </div>
      </NcModal>
      <NcModal
          v-if="modalEpisodeDescription"
          @close="closeModalEpisodeDescription"
          size="large"
          title="Episode description"
          :outTransition="true">
        <div class="modal__content">
          <h2 v-if="isLoading">Loading episode description"</h2>
          <h2 v-else>Episode description</h2>
          <div v-html="getEpisodeDescription()"></div>
        </div>
      </NcModal>
    </template>
    <template #actions>
      <NcActionLink :href="action.podcastUrl"
                  target="_blank"
                  icon="icon-external">
        Open RSS feed
      </NcActionLink>
      <NcActionLink :href="action.episodeUrl"
                  target="_blank"
                  icon="icon-external">
        Download episode media
      </NcActionLink>
      <NcActionButton @click="showModalPlayer"
                  icon="icon-play">
        Play episode media
      </NcActionButton>
      <NcActionLink v-if="!isLoading"
                  :href="getEpisodeLink()"
                  target="_blank"
                  icon="icon-external">
        Open episode link
      </NcActionLink>
      <NcActionLink v-if="!isLoading"
                  @click="postNote"
                  icon="icon-new-document">
        Create note
      </NcActionLink>
    </template>
	</NcListItem>
</template>

<script>
import NcActionLink from '@nextcloud/vue/dist/Components/NcActionLink'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar'
import NcListItem from '@nextcloud/vue/dist/Components/NcListItem'
import NcModal from '@nextcloud/vue/dist/Components/NcModal'

import Rss from 'vue-material-design-icons/Rss.vue'
import TurndownService from 'turndown'

import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

export default {
	name: 'ActionListItem',
	components: {
		NcActionLink,
    NcActionButton,
		NcAvatar,
		NcListItem,
		Rss,
    NcModal,
	},
	props: {
		action: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			actionExtraData: null,
			isLoading: true,
      modalPlayer: false,
      modalEpisodeDescription: false,
		}
	},
	async mounted() {
    await this.loadActionExtraData();
	},
	methods: {
		async loadActionExtraData() {
      try {
        const resp = await axios.get(generateUrl('/apps/nextpod/personal_settings/action_extra_data?episodeUrl={url}', {
          url: this.action.episodeUrl,
        }))
        this.actionExtraData = resp.data?.data
      } catch (e) {
        console.error(e)
      } finally {
        this.isLoading = false
      }
      this.isLoading = false
		},
		getEpisode() {
			return this.action.episodeUrl ?? 'episodeUrl'
		},
    getTimeString(seconds) {
      const hours = Math.floor(seconds / 3600)
      const modMinutes = Math.floor(seconds / 60) % 60
      if (hours === 0) {
        const modSeconds = seconds % 60
        return `${modMinutes}min ${modSeconds}s`
      }
      return `${hours}h ${modMinutes}min`
    },
		getDetails() {
      if (this.action.position === -1 || this.action.total === -1) {
        return '';
      }

      if (this.action.position === this.action.total) {
        return `(done, ${this.getTimeString(this.action.total)})`;
      }

      const percent = Math.round(this.action.position / this.action.total * 100);

			return `(${percent}% of ${this.getTimeString(this.action.total)} listened)`;
		},
    getImageSrc() {
      return this.actionExtraData?.episodeImage ?? ''
    },
		getPodcastName() {
			return this.actionExtraData?.podcastName ?? ''
		},
    getEpisodeName() {
      return this.actionExtraData?.episodeName ?? this.action.episodeUrl;
    },
    getEpisodeLink() {
      return this.actionExtraData?.episodeLink ?? ''
    },
    getEpisodeDescription() {
      return this.actionExtraData?.episodeDescription ?? '';
    },
    showModalPlayer() {
      this.modalPlayer = true
    },
    closeModalPlayer() {
      this.modalPlayer = false
    },
    showModalEpisodeDescription() {
      this.modalEpisodeDescription = true
    },
    closeModalEpisodeDescription() {
      this.modalEpisodeDescription = false
    },
    async postNote() {
      const turndownService = new TurndownService();
      const descriptionMarkdown = turndownService.turndown(this.getEpisodeDescription());

      const resp = await axios.post(generateUrl('/apps/notes/api/v1/notes'), {
        title: this.getEpisodeName(),
        content: this.getEpisodeName() + '\n========\n\n' + descriptionMarkdown,
      })
          .then(function (response) {
            console.log(response);
          })
          .catch(function (error) {
            console.log(error);
          });
    }
  },
  watch: {
    'action.episodeUrl'(val) {
      this.loadActionExtraData();
    }
  }
}
</script>

<style lang="scss" scoped>
.modal__content {
  margin: 50px;
  text-align: center;
}
</style>
