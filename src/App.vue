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
			<div>This is the content</div>
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
	},
	data() {
		return {
			loading: false,
			show: true,
			starred: false,
		}
	},
	methods: {
		close() {
			this.show = false
			console.debug(arguments)
		},
		newButtonAction() {
			console.debug(arguments)
		},
		log() {
			console.debug(arguments)
		},
	},
}
</script>
