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
          <audio controls autoplay ref="audio" @canplaythrough="playAudio" @timeupdate='onTimeUpdateListener'>
            <source :src="action.episodeUrl">
            Your browser does not support the audio element.
          </audio>
          <p>
            <input id="store-play-progress" type="checkbox" class="checkbox" v-model="storePlayProgress" />
            <label for="store-play-progress">Store progress while playing</label>
          </p>
        </div>
      </NcModal>
      <NcModal
          v-if="modalEpisodeDescription"
          @close="closeModalEpisodeDescription"
          size="large"
          title="Episode description"
          :outTransition="true">
        <div class="modal__content description-content">
          <h2 v-if="isLoading">Loading episode description...</h2>
          <h2 v-else v-html="getEpisodeName()">Episode description</h2>
          <h3 v-html="getPodcastName()"></h3>
          <div v-html="getEpisodeDescription()"></div>
        </div>
      </NcModal>
    </template>
    <template #actions>
      <NcActionLink v-if="!isLoading"
                    :href="getEpisodeLink()"
                    target="_blank"
                    icon="icon-external">
        Open episode link
      </NcActionLink>
      <NcActionButton @click="showModalPlayer"
                      icon="icon-play">
        Play episode media
      </NcActionButton>
      <NcActionButton v-if="!isLoading && hasNotesApp"
                      @click="postNote"
                      icon="icon-notes">
        Create note of episode
      </NcActionButton>
      <NcActionLink :href="action.episodeUrl"
                  target="_blank"
                  icon="icon-external">
        Download episode media
      </NcActionLink>
      <NcActionLink :href="action.podcastUrl"
                    target="_blank"
                    icon="icon-external">
        Open RSS feed
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
    hasNotesApp: {
			type: Boolean,
			required: true,
		},
	},
	data() {
		return {
			actionExtraData: null,
			isLoading: true,
      modalPlayer: false,
      modalEpisodeDescription: false,
      playTimeSet: false,
      storePlayProgress: false,
		}
	},
	async mounted() {
    await this.loadActionExtraData();
	},
	methods: {
    playAudio() {
      // const audio = document.querySelector('audio');
      const audio = this.$refs.audio;

      if (!this.playTimeSet) {
        if (this.action.position !== this.action.total && this.action.position !== -1) {
          audio.currentTime = this.action.position;
          this.playTimeSet = true;
        }

        audio.play();
      }
    },
    async onTimeUpdateListener() {
      if (this.storePlayProgress && this.$refs.audio) {
        this.action.position = Math.round(this.$refs.audio.currentTime);
        const date = new Date();
        const actionPayload = {
              "podcast": this.action.podcastUrl,
              "episode": this.action.episodeUrl,
              "guid": this.action.guid,
              "action": "PLAY",
              "timestamp": this.getCurrentDateTime(),

              "started": this.action.started,
              "position": this.action.position,
              "total":  this.action.total
            };

        const resp = await axios.post(generateUrl('/apps/gpoddersync/episode_action/create'), [
            actionPayload
        ])
      }
    },
    getCurrentDateTime() {
      const now = new Date();

      // We don't want date.toISOString(), because it returns the time with timezone
      const year = now.getFullYear().toString().padStart(4, '0');
      const month = (now.getMonth() + 1).toString().padStart(2, '0');
      const day = now.getDate().toString().padStart(2, '0');
      const hour = now.getHours().toString().padStart(2, '0');
      const minute = now.getMinutes().toString().padStart(2, '0');
      const second = now.getSeconds().toString().padStart(2, '0');

      return `${year}-${month}-${day}T${hour}:${minute}:${second}`;
    },
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
      this.playTimeSet = false;
      this.modalPlayer = true;
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
      const episodeName = this.getEpisodeName();
      const headlineMarker = '='.repeat(episodeName.length);
      const episodeLinkMarkdown = `[Episode](${this.getEpisodeLink()})`;
      const podcastLinkMarkdown = `[Podcast RSS](${this.action.podcastUrl})`;

      const resp = await axios.post(generateUrl('/apps/notes/api/v1/notes'), {
        title: episodeName,
        content: `${episodeName}\n${headlineMarker}\n\n${episodeLinkMarkdown} | ${podcastLinkMarkdown}\n\n${descriptionMarkdown}`,
      })
      .then(function (response) {
        const noteId = response.data.id;

        // Attempt to open new tab with note
        window.open(generateUrl('/apps/notes/note/{id}', {
          id: noteId,
        }), '_blank').focus();

        // window.location.href = generateUrl('/apps/notes/note/{id}', {
        //   id: noteId,
        // });
      })
      .catch(function (error) {
        console.error(error);
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

  // Doesn't seem to work for images in the episode description
  img {
    max-width: 100%;
  }
}

.description-content > div {
  text-align: left;
}
</style>

<!-- These styles didn't work "scoped" -->
<style lang="scss">
.description-content {
  user-select: text;

  h2, h3, div > p {
    cursor: text;
  }

  img {
    max-width: 100%;
  }

  a.description-link {
    text-decoration: underline;
    color: var(--color-text-lighter);
  }
}
</style>
