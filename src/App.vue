<template>
	<NcContent :class="{'icon-loading': loading}" app-name="gpoddersync">
		<NcAppNavigation>
			<template id="app-gpoddersync-navigation" #list>
<!--				<NcAppNavigationItem to="/actions" :to="{name: 'episodes'}" title="Episodes">-->
				<NcAppNavigationItem to="/actions" title="Episodes">
          <template #icon>
            <FileMusic />
          </template>
          <template #counter>
            <NcCounterBubble v-if="actionsAmount > 0">
              {{ actionsAmount }}
            </NcCounterBubble>
          </template>
				</NcAppNavigationItem>
<!--				<NcAppNavigationItem to="/podcasts" :to="{name: 'podcasts'}" title="Podcasts">-->
				<NcAppNavigationItem to="/podcasts" title="Podcasts">
          <template #icon>
            <Podcast />
          </template>
          <template #counter>
            <NcCounterBubble v-if="subscriptionsAmount > 0">
              {{ subscriptionsAmount }}
            </NcCounterBubble>
          </template>
				</NcAppNavigationItem>
<!--				<NcAppNavigationItem title="Loading Item" :loading="true" />-->
			</template>

<!--			<template #footer>-->
<!--				<NcAppNavigationSettings>-->
<!--					Example settings-->
<!--				</NcAppNavigationSettings>-->
<!--			</template>-->
		</NcAppNavigation>

		<NcAppContent>
      <router-view @subscriptionsAmount="setSubscriptionsAmount" @actionsAmount="setActionsAmount" />
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
    NcSettingsSection,
    SubscriptionListItem,
    ActionListItem,
    NcActions,
	},
  data() {
    return {
      loading: false,
      actionsAmount: 0,
      subscriptionsAmount: 0,
      reload: false,
    }
  },
  methods: {
    setActionsAmount(actionsAmount) {
      this.actionsAmount = actionsAmount;
    },
    setSubscriptionsAmount(subscriptionsAmount) {
      this.subscriptionsAmount = subscriptionsAmount;
    }
  }
}
</script>

<style lang="scss" scoped>
button.app-navigation-toggle {
  display: none;
}
</style>
