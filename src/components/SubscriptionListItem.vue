<template>
  <NcListItem
    :name="getTitle()"
    :details="getDetails()"
    :force-display-actions="true"
  >
    <template #icon>
      <NcAvatar
        :size="44"
        :url="getImageSrc()"
        :display-name="getAvatarName()"
      />
    </template>
    <template #subname>
      <span v-if="isLoading">
        <em>{{ t("nextpod", "(Loading RSS data...)") }}</em>
      </span>
      <span v-else>{{ getSubtitle() }}</span>
    </template>
    <template #actions>
      <NcActionLink
        v-if="getHomepageLink()"
        :href="getHomepageLink()"
        target="_blank"
      >
        <template #icon>
          <OpenInNew :size="20" />
        </template>
        {{ t("nextpod", "Podcast's homepage") }}
      </NcActionLink>
      <NcActionLink :href="getRssLink()" target="_blank">
        <template #icon>
          <Rss :size="20" />
        </template>
        {{ t("nextpod", "RSS feed") }}
      </NcActionLink>
    </template>
  </NcListItem>
</template>

<script>
import { NcActionLink, NcAvatar, NcListItem } from "@nextcloud/vue";

import Rss from "vue-material-design-icons/Rss.vue";
import OpenInNew from "vue-material-design-icons/OpenInNew.vue";

import { generateUrl } from "@nextcloud/router";
import { translate as t } from "@nextcloud/l10n";
import axios from "@nextcloud/axios";

export default {
  name: "SubscriptionListItem",
  components: {
    NcActionLink,
    NcAvatar,
    NcListItem,
    Rss,
    OpenInNew,
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
    };
  },
  async mounted() {
    await this.loadPodcastData();
  },
  methods: {
    async loadPodcastData() {
      try {
        const resp = await axios.get(
          generateUrl(
            "/apps/nextpod/personal_settings/podcast_data?url={url}",
            {
              url: this.sub.url,
            },
          ),
        );
        this.podcastData = resp.data?.data;
      } catch (e) {
        console.error(e);
      } finally {
        this.isLoading = false;
      }
    },
    getTitle() {
      return this.podcastData?.title ?? this.sub.url ?? "";
    },
    getDetails() {
      if (this.sub.listenedSeconds <= 0) {
        return t("nextpod", "(no time listened)");
      }
      const seconds = this.sub.listenedSeconds;
      const hours = Math.floor(seconds / 3600);
      const modMinutes = Math.floor(seconds / 60) % 60;
      if (hours === 0) {
        const modSeconds = seconds % 60;
        return t("nextpod", "({minutes}min {seconds}s listened)", {
          minutes: modMinutes,
          seconds: modSeconds,
        });
      }
      return t("nextpod", "({hours}h {minutes}min listened)", {
        hours,
        minutes: modMinutes,
      });
    },
    getImageSrc() {
      return this.podcastData?.imageBlob ?? this.podcastData?.imageUrl ?? "";
    },
    getAvatarName() {
      return this.podcastData?.author ?? "";
    },
    getSubtitle() {
      return this.podcastData?.description ?? "";
    },
    getHomepageLink() {
      return this.podcastData?.link ?? "";
    },
    getRssLink() {
      return this.sub.url ?? "";
    },
  },
  watch: {
    "sub.url"(val) {
      this.loadPodcastData();
    },
  },
};
</script>

<style lang="scss" scoped>
a.link {
  text-decoration: underline;
  color: var(--color-primary-element-light);
}
</style>
