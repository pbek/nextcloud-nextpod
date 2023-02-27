<template>
	<div class="gpoddersync-navigation" role="toolbar">
		<!-- Main Navigation title -->
		<div class="gpoddersync-navigation__title">
			<h2 class="gpoddersync-navigation__title__main" @click="refresh">
				{{ name }}
			</h2>
			<div class="gpoddersync-navigation__title__sub" />
			<slot name="subtitle" />
		</div>

		<!-- Main slot -->
		<div v-if="$slots.default" class="gpoddersync-navigation__content">
			<slot />
		</div>

		<NcLoadingIcon v-show="loading" class="gpoddersync-navigation__loader" />

		<div class="gpoddersync-navigation__content-right">
			<slot name="right" />
		</div>
	</div>
</template>

<script>
import { NcButton, NcLoadingIcon } from '@nextcloud/vue'

export default {
	name: 'HeaderNavigation',

	components: {
		NcButton,
		NcLoadingIcon,
	},

	inheritAttrs: false,

	props: {
		loading: {
			type: Boolean,
			default: false,
		},
		path: {
			type: String,
			default: '/',
		},
		title: {
			type: String,
			required: true,
		},
		rootTitle: {
			type: String,
			default: t('gpoddersyn', 'Podcasts'),
		},
		// The route params
		params: {
			type: Object,
			default: null,
		},
	},

	computed: {
		name() {
			return this.title
		},
	},

	methods: {
		refresh() {
			this.$emit('refresh')
		},

		toggleNavigationButton(hide) {
			// Hide the navigation toggle if the back button is shown
			const navigationToggle = document.querySelector('button.app-navigation-toggle')
			if (navigationToggle !== null) {
				navigationToggle.style.display = hide ? 'none' : null
			}
		},
	},
}
</script>

<style lang="scss">
:root {
	--gpoddersync-navigation-height: 64px;
	// header height - button size
	--gpoddersync-navigation-spacing: calc((var(--gpoddersync-navigation-height) - 44px) / 2);
}

// Properly position the navigation toggle button
button.app-navigation-toggle {
	// App-navigation have a 4px margin top
	top: 0 !important;
	right: calc(var(--gpoddersync-navigation-height) * -1) !important;
	margin: var(--gpoddersync-navigation-spacing) !important;
}

</style>

<style lang="scss" scoped>
.gpoddersync-navigation {
	position: sticky;
	z-index: 20;
	top: 0;
	display: flex;
	align-items: center;
	width: 100%;
	min-height: var(--gpoddersync-navigation-height);
	padding: 0 var(--gpoddersync-navigation-height);
	background: var(--color-main-background);
  margin-top: 10px;

	&__back {
		// Above the navigation menu
		position: absolute !important;
		left: 0;
		margin: var(--gpoddersync-navigation-spacing) !important;
	}

	&__title {
		//max-width: 50%;
		margin-right: calc(2 * var(--gpoddersync-navigation-spacing));
		display: flex;
		flex-direction: column;

		&__main {
			margin: 0;
			cursor: pointer;
		}

		&__main, &__sub {
			overflow: hidden;
			white-space: nowrap;
			text-overflow: ellipsis;
		}
	}

	&__loader {
		margin-left: 32px;
	}

	&__content-right {
		display: flex;
		align-items: center;
		justify-content: center;
		margin-left: auto;
	}
}

</style>
