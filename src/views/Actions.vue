<template>
  <div>
    <HeaderNavigation key="navigation"
                      :loading="isLoading"
                      :title="t('nextpod', 'Episode actions')"
                      @refresh="loadData">
      <template #subtitle>
        {{ t('nextpod', 'Last episode actions synchronized to this Nextcloud account so far.')}}
      </template>
    </HeaderNavigation>

    <div v-if="actions.length > 0" class="actions">
      <div class="sorting-container">
        <label for="nextpod_action_filtering">Action:</label>
        <NcMultiselect id="nextpod_action_filtering"
                       v-model="actionFilter"
                       :options="actionFilteringOptions"
                       track-by="label"
                       label="label"
                       :allow-empty="false"
                       @change="updateActionFiltering" />
      </div>
      <ul>
        <ActionListItem v-for="action in actions.slice(0, maxActions)"
                        :key="action.episode"
                        :action="action"
                        :hasNotesApp="hasNotesApp" />
      </ul>
      <NcActions>
        <NcActionButton
            :disabled="actions.length < maxActions"
            @click="loadMoreActions">
          <template #icon>
            <PageNext />
          </template>
          {{ t('nextpod', 'Load more') }}
        </NcActionButton>
      </NcActions>
    </div>
    <div v-if="actions.length === 0 && !isLoading">
      <NcEmptyContent>
        <template #icon>
          <Podcast />
        </template>
        <template #title>
          <h1>No episode actions</h1>
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
import { loadState } from '@nextcloud/initial-state'

const actionFilteringOptions = [
  { label: 'Play', action: 'PLAY' },
  { label: 'Download', action: 'DOWNLOAD' },
]

export default {
  name: 'Actions',
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
      allActions: [],
      actions: [],
      maxActions: 10,
      isLoading: true,
      actionFilter: actionFilteringOptions[0],
      actionFilteringOptions,
      hasNotesApp: false,
    }
  },
  async mounted() {
    this.checkNotesApp();
    await this.loadData();
  },
  methods: {
    checkNotesApp() {
      this.hasNotesApp = loadState('nextpod', 'has-notes-app')
    },
    async loadData() {
      this.isLoading = true
      try {
        const resp = await axios.get(generateUrl('/apps/nextpod/personal_settings/metrics'))
        if (!Array.isArray(resp.data.actions)) {
          throw new Error('expected actions array in metrics response')
        }
        this.allActions = resp.data.actions;
        this.$emit('actionsAmount', this.allActions.length)
        this.$emit('subscriptionsAmount', resp.data.subscriptions.length)
        this.updateActionFiltering(this.actionFilter);
      } catch (e) {
        console.error(e)
        showError(t('nextpod', 'Could not fetch podcast synchronization stats'))
      } finally {
        this.isLoading = false
      }
    },
    updateActionFiltering(filtering) {
      this.actions = this.allActions.filter(obj => obj.action === filtering.action)
    },
    loadMoreActions() {
      this.maxActions += 10;
    },
  },
}
</script>

<style lang="scss" scoped>
.empty-content h1 {
  font-size: 1.5rem;
  font-weight: 500;
  margin: 0;
  margin-bottom: 1rem;
}

div.actions {
  padding: 20px var(--nextpod-navigation-height) 0 var(--nextpod-navigation-height);
}

a.link {
  text-decoration: underline;
  color: var(--color-primary-element);
}
</style>
