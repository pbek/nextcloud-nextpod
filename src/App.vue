<template>
  <NcContent :class="{ 'icon-loading': loading }" app-name="nextpod">
    <NcAppNavigation>
      <template id="app-nextpod-navigation" #list>
        <!--				<NcAppNavigationItem to="/actions" :to="{name: 'episodes'}" title="Episodes">-->
        <NcAppNavigationItem to="/actions" name="Episodes">
          <template #icon>
            <FileMusic :size="20" />
          </template>
          <template #counter>
            <NcCounterBubble v-if="actionsAmount > 0">
              {{ actionsAmount }}
            </NcCounterBubble>
          </template>
        </NcAppNavigationItem>
        <!--				<NcAppNavigationItem to="/podcasts" :to="{name: 'podcasts'}" title="Podcasts">-->
        <NcAppNavigationItem to="/podcasts" name="Podcasts">
          <template #icon>
            <Podcast :size="20" />
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
      <router-view
        @subscriptionsAmount="setSubscriptionsAmount"
        @actionsAmount="setActionsAmount"
      />
    </NcAppContent>
  </NcContent>
</template>

<script>
import {
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
  NcEmptyContent,
  NcSelect as NcMultiselect,
  NcSettingsSection,
  NcActions,
} from "@nextcloud/vue";
import RotateRight from "vue-material-design-icons/RotateRight.vue";
import Podcast from "vue-material-design-icons/Podcast.vue";
import FileMusic from "vue-material-design-icons/FileMusic.vue";
import SubscriptionListItem from "./components/SubscriptionListItem.vue";
import ActionListItem from "./components/ActionListItem.vue";

export default {
  name: "App",
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
    };
  },
  methods: {
    setActionsAmount(actionsAmount) {
      this.actionsAmount = actionsAmount;
    },
    setSubscriptionsAmount(subscriptionsAmount) {
      this.subscriptionsAmount = subscriptionsAmount;
    },
  },
};
</script>

<style lang="scss" scoped>
button.app-navigation-toggle {
  display: none;
}
</style>
