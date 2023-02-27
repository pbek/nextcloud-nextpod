<template>
  <div>
    <HeaderNavigation key="navigation"
                      :loading="isLoading"
                      :title="t('gpoddersync', 'Synced subscriptions')"
                      @refresh="loadData">
      <template #subtitle>
        {{ t('gpoddersync', 'Podcast subscriptions synchronized to this Nextcloud account so far.')}}
      </template>
    </HeaderNavigation>
    <div v-if="subscriptions.length > 0" class="podcasts">
      <div class="sorting-container">
        <label for="gpoddersync_sorting">Sort by:</label>
        <NcMultiselect id="gpoddersync_sorting"
                       v-model="sortBy"
                       :options="sortingOptions"
                       track-by="label"
                       label="label"
                       :allow-empty="false"
                       @change="updateSorting" />
      </div>
      <ul>
        <SubscriptionListItem v-for="sub in subscriptions"
                              :key="sub.url"
                              :sub="sub" />
      </ul>
    </div>
    <div v-if="subscriptions.length === 0 && !isLoading">
      <NcEmptyContent>
        No subscriptions
        <template #icon>
          <Podcast />
        </template>
        <template #desc>
          Start syncing podcasts from your favorite podcast client, such as
          <a class="link" href="https://antennapod.org/" target="_blank">Antennapod</a>,
          and then refresh this page to see them pop up here.
        </template>
      </NcEmptyContent>
    </div>
  </div>
</template>

<script>
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent'
import NcMultiselect from '@nextcloud/vue/dist/Components/NcMultiselect'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection'
import SubscriptionListItem from '../components/SubscriptionListItem.vue'
import ActionListItem from '../components/ActionListItem.vue'
import NcActions from '@nextcloud/vue/dist/Components/NcActions'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton'

import Podcast from 'vue-material-design-icons/Podcast'
import PageNext from 'vue-material-design-icons/PageNext.vue'

import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import HeaderNavigation from "./HeaderNavigation.vue";

const sortingOptions = [
  { label: 'Listened time (desc)', compare: (a, b) => a?.listenedSeconds < b?.listenedSeconds },
  { label: 'Listened time (asc)', compare: (a, b) => a?.listenedSeconds > b?.listenedSeconds },
]

export default {
  name: 'Podcasts',
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
  },
  data() {
    return {
      subscriptions: [],
      isLoading: true,
      sortBy: sortingOptions[0],
      sortingOptions,
    }
  },
  async mounted() {
    await this.loadData();
  },
  methods: {
    async loadData() {
      this.isLoading = true
      try {
        const resp = await axios.get(generateUrl('/apps/gpoddersync/personal_settings/metrics'))
        if (!Array.isArray(resp.data.subscriptions)) {
          throw new Error('expected subscriptions array in metrics response')
        }
        this.subscriptions = resp.data.subscriptions
        this.$emit('subscriptionsAmount', this.subscriptions.length)
        this.$emit('actionsAmount', resp.data.actions.length)
        this.subscriptions.sort(this.sortBy.compare)
      } catch (e) {
        console.error(e)
        showError(t('gpoddersync', 'Could not fetch podcast synchronization stats'))
      } finally {
        this.isLoading = false
      }
    },
    updateSorting(sorting) {
      this.subscriptions.sort(sorting.compare)
    },
  },
}
</script>

<style lang="scss" scoped>
div.podcasts {
  padding: 20px var(--gpoddersync-navigation-height) 0 var(--gpoddersync-navigation-height);
}

a.link {
  text-decoration: underline;
  color: var(--color-primary-element);
}
</style>
