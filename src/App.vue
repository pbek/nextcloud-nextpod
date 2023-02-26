<template>
	<NcContent :class="{'icon-loading': loading}" app-name="gpoddersync">
		<NcAppNavigation>
			<template id="app-gpoddersync-navigation" #list>
				<NcAppNavigationItem title="Episodes">
          <template #icon>
            <FileMusic />
          </template>
          <template #counter>
            <NcCounterBubble>
              99+
            </NcCounterBubble>
          </template>
          <template #actions>
            <NcActionButton @click="alert('Reload')" title="Reload">
              <template #icon>
                <RotateRight />
              </template>
            </NcActionButton>
          </template>
				</NcAppNavigationItem>
				<NcAppNavigationItem title="Podcasts">
          <template #icon>
            <Podcast />
          </template>
          <template #counter>
            <NcCounterBubble>
              99+
            </NcCounterBubble>
          </template>
          <template #actions>
            <NcActionButton @click="alert('Reload')" title="Reload">
              <template #icon>
                <RotateRight />
              </template>
            </NcActionButton>
          </template>
				</NcAppNavigationItem>
				<NcAppNavigationItem title="Loading Item" :loading="true" />
			</template>

			<template #footer>
				<NcAppNavigationSettings>
					Example settings
				</NcAppNavigationSettings>
			</template>
		</NcAppNavigation>

		<NcAppContent>
      <NcSettingsSection :title="t('gpoddersync', 'Last actions')"
                         :description="t('gpoddersync', 'A list of last actions.')">
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
      </NcSettingsSection>
      <NcSettingsSection :title="t('gpoddersync', 'Synced subscriptions')"
                         :description="t('gpoddersync', 'Podcast subscriptions synchronized to this Nextcloud account so far.')">
        <div v-if="subscriptions.length > 0">
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
      </NcSettingsSection>

    </NcAppContent>
	</NcContent>
</template>

<script>
import NcContent from '@nextcloud/vue/dist/Components/NcContent'
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/dist/Components/NcAppNavigation'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem'
import NcAppNavigationNew from '@nextcloud/vue/dist/Components/NcAppNavigationNew'
import NcAppNavigationSettings from '@nextcloud/vue/dist/Components/NcAppNavigationSettings'
import NcAppSidebar from '@nextcloud/vue/dist/Components/NcAppSidebar'
import NcAppSidebarTab from '@nextcloud/vue/dist/Components/NcAppSidebarTab'
import NcCounterBubble from '@nextcloud/vue/dist/Components/NcCounterBubble'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton'
import NcActionLink from '@nextcloud/vue/dist/Components/NcActionLink'
import NcAppNavigationIconBullet from '@nextcloud/vue/dist/Components/NcAppNavigationIconBullet'
import NcActionCheckbox from '@nextcloud/vue/dist/Components/NcActionCheckbox'
import NcActionInput from '@nextcloud/vue/dist/Components/NcActionInput'
import NcActionRouter from '@nextcloud/vue/dist/Components/NcActionRouter'
import NcActionText from '@nextcloud/vue/dist/Components/NcActionText'
import NcActionTextEditable from '@nextcloud/vue/dist/Components/NcActionTextEditable'
import RotateRight from 'vue-material-design-icons/RotateRight.vue'
import Podcast from 'vue-material-design-icons/Podcast.vue'
import FileMusic from 'vue-material-design-icons/FileMusic.vue'
import NcEmptyContent from "@nextcloud/vue/dist/Components/NcEmptyContent";
import NcMultiselect from "@nextcloud/vue/dist/Components/NcMultiselect";
import NcSettingsSection from "@nextcloud/vue/dist/Components/NcSettingsSection";
import SubscriptionListItem from "./components/SubscriptionListItem.vue";
import ActionListItem from "./components/ActionListItem.vue";
import NcActions from "@nextcloud/vue/dist/Components/NcActions";
import PageNext from 'vue-material-design-icons/PageNext.vue'

import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'

const sortingOptions = [
  { label: 'Listened time (desc)', compare: (a, b) => a?.listenedSeconds < b?.listenedSeconds },
  { label: 'Listened time (asc)', compare: (a, b) => a?.listenedSeconds > b?.listenedSeconds },
]

const actionFilteringOptions = [
  { label: 'Play', action: 'PLAY' },
  { label: 'Download', action: 'DOWNLOAD' },
]

export default {
	name: 'App',
	components: {
		NcContent,
    NcAppContent,
		NcAppNavigation,
		NcAppNavigationItem,
		NcAppNavigationNew,
		NcAppNavigationSettings,
    NcAppSidebar,
    NcAppSidebarTab,
		NcCounterBubble,
		NcActionButton,
		NcActionLink,
		NcAppNavigationIconBullet,
    NcActionCheckbox,
    NcActionInput,
    NcActionRouter,
    NcActionText,
    NcActionTextEditable,
    RotateRight,
    Podcast,
    FileMusic,
    NcEmptyContent,
    NcMultiselect,
    PageNext,
    NcSettingsSection,
    SubscriptionListItem,
    ActionListItem,
    NcActions,
	},
  data() {
    return {
      loading: false,
      subscriptions: [],
      allActions: [],
      actions: [],
      maxActions: 10,
      isLoading: true,
      sortBy: sortingOptions[0],
      sortingOptions,
      actionFilter: actionFilteringOptions[0],
      actionFilteringOptions,
    }
  },
  async mounted() {
    emit('toggle-navigation', {
      open: true,
    })

    try {
      const resp = await axios.get(generateUrl('/apps/gpoddersync/personal_settings/metrics'))
      if (!Array.isArray(resp.data.actions)) {
        throw new Error('expected actions array in metrics response')
      }
      this.allActions = resp.data.actions;
      this.updateActionFiltering(this.actionFilter);
      if (!Array.isArray(resp.data.subscriptions)) {
        throw new Error('expected subscriptions array in metrics response')
      }
      this.subscriptions = resp.data.subscriptions
      this.subscriptions.sort(this.sortBy.compare)
    } catch (e) {
      console.error(e)
      showError(t('gpoddersync', 'Could not fetch podcast synchronization stats'))
    } finally {
      this.isLoading = false
    }
  },
  methods: {
    updateSorting(sorting) {
      this.subscriptions.sort(sorting.compare)
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
a.link {
  text-decoration: underline;
  color: var(--color-primary-element);
}
</style>
