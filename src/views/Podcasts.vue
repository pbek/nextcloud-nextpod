<template>
  <div>
    <HeaderNavigation
      key="navigation"
      :loading="isLoading"
      :title="t('nextpod', 'Subscriptions')"
      @refresh="loadData"
    >
      <template #subtitle>
        {{
          t(
            "nextpod",
            "Podcast subscriptions synchronized to this Nextcloud account so far.",
          )
        }}
      </template>
    </HeaderNavigation>
    <div v-if="subscriptions.length > 0" class="podcasts">
      <div class="sorting-container">
        <label for="nextpod_sorting">
          {{ t("nextpod", "Sort by:") }}
        </label>
        <NcMultiselect
          id="nextpod_sorting"
          v-model="sortBy"
          :options="sortingOptions"
          track-by="label"
          label="label"
          :allow-empty="false"
          @update:model-value="updateSorting"
        />
      </div>
      <ul>
        <SubscriptionListItem
          v-for="sub in subscriptions"
          :key="sub.url"
          :sub="sub"
        />
      </ul>
    </div>
    <div v-if="subscriptions.length === 0 && !isLoading">
      <NcEmptyContent>
        <template #icon>
          <Podcast />
        </template>
        <template #title>
          <h1>{{ t("nextpod", "No subscriptions") }}</h1>
          <NcRichText
            :text="
              t(
                'nextpod',
                'Start syncing podcasts from your favorite podcast client, such as {client}, and then refresh this page to see them pop up here.',
                { client: '[AntennaPod](https://antennapod.org/)' },
              )
            "
            :use-markdown="true"
          />
        </template>
      </NcEmptyContent>
    </div>
  </div>
</template>

<script>
import {
  NcEmptyContent,
  NcSelect as NcMultiselect,
  NcSettingsSection,
  NcActions,
  NcActionButton,
  NcRichText,
} from "@nextcloud/vue";
import SubscriptionListItem from "../components/SubscriptionListItem.vue";
import ActionListItem from "../components/ActionListItem.vue";

import Podcast from "vue-material-design-icons/Podcast";
import PageNext from "vue-material-design-icons/PageNext.vue";

import { generateUrl } from "@nextcloud/router";
import { translate as t } from "@nextcloud/l10n";
import axios from "@nextcloud/axios";
import { showError } from "@nextcloud/dialogs";
import HeaderNavigation from "./HeaderNavigation.vue";

export default {
  name: "Podcasts",
  components: {
    HeaderNavigation,
    NcEmptyContent,
    NcMultiselect,
    Podcast,
    PageNext,
    NcSettingsSection,
    SubscriptionListItem,
    ActionListItem,
    NcActionButton,
    NcActions,
    NcRichText,
  },
  data() {
    const sortingOptions = [
      {
        label: t("nextpod", "Listened time (desc)"),
        compare: (a, b) => a?.listenedSeconds < b?.listenedSeconds,
      },
      {
        label: t("nextpod", "Listened time (asc)"),
        compare: (a, b) => a?.listenedSeconds > b?.listenedSeconds,
      },
    ];

    return {
      subscriptions: [],
      isLoading: true,
      sortBy: sortingOptions[0],
      sortingOptions,
      refreshIntervalId: null,
    };
  },
  async mounted() {
    await this.loadData();
    this.refreshIntervalId = setInterval(this.loadData, 60000);
  },
  async beforeUnmount() {
    clearInterval(this.refreshIntervalId);
  },
  methods: {
    async loadData() {
      this.isLoading = true;
      try {
        const resp = await axios.get(
          generateUrl("/apps/nextpod/personal_settings/metrics"),
        );
        if (!Array.isArray(resp.data.subscriptions)) {
          throw new Error("expected subscriptions array in metrics response");
        }
        this.subscriptions = resp.data.subscriptions;
        this.$emit("subscriptionsAmount", this.subscriptions.length);
        this.$emit("actionsAmount", resp.data.actions.length);
        this.subscriptions.sort(this.sortBy.compare);
      } catch (e) {
        console.error(e);
        showError(
          t("nextpod", "Could not fetch podcast synchronization stats"),
        );
      } finally {
        this.isLoading = false;
      }
    },
    updateSorting(sorting) {
      this.subscriptions.sort(sorting.compare);
    },
  },
};
</script>

<style lang="scss" scoped>
.empty-content h1 {
  font-size: 1.5rem;
  font-weight: 500;
  margin: 0;
  margin-bottom: 1rem;
}

div.podcasts {
  padding: 20px var(--nextpod-navigation-height) 0
    var(--nextpod-navigation-height);
}
</style>
