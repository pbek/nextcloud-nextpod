<template>
  <div>
    <HeaderNavigation key="navigation"
                      :loading="isLoading"
                      :title="t('gpoddersync', 'Last actions')"
                      @refresh="loadData">
      <template #subtitle>
        {{ t('gpoddersync', 'A list of last actions.')}}
      </template>
    </HeaderNavigation>

    <div v-if="actions.length > 0" class="actions">
      <div class="sorting-container">
        <label for="gpoddersync_action_filtering">Action:</label>
        <NcMultiselect id="gpoddersync_action_filtering"
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
                        :action="action" />
      </ul>
      <NcActions>
        <NcActionButton
            :disabled="actions.length < maxActions"
            @click="loadMoreActions">
          <template #icon>
            <PageNext />
          </template>
          {{ t('gpoddersync', 'Load more') }}
        </NcActionButton>
      </NcActions>
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
import { emit } from '@nextcloud/event-bus'
import HeaderNavigation from "./HeaderNavigation.vue";

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
        if (!Array.isArray(resp.data.actions)) {
          throw new Error('expected actions array in metrics response')
        }
        this.allActions = resp.data.actions;
        this.$emit('actionsAmount', this.allActions.length)
        this.$emit('subscriptionsAmount', resp.data.subscriptions.length)
        this.updateActionFiltering(this.actionFilter);
      } catch (e) {
        console.error(e)
        showError(t('gpoddersync', 'Could not fetch podcast synchronization stats'))
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
div.actions {
  padding: 20px var(--gpoddersync-navigation-height) 0 var(--gpoddersync-navigation-height);
}

a.link {
  text-decoration: underline;
  color: var(--color-primary-element);
}
</style>
