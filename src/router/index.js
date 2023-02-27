import { generateUrl } from '@nextcloud/router'
import Router from 'vue-router'
import Vue from 'vue'

const Actions = () => import('../views/Actions')
const Podcasts = () => import('../views/Podcasts')

const baseTitle = document.title

Vue.use(Router)

const router = new Router({
	mode: 'history',
	// if index.php is in the url AND we got this far, then it's working:
	// let's keep using index.php in the url
	base: generateUrl('/apps/gpoddersync'),
	linkActiveClass: 'active',
	routes: [
		{
			path: '/',
			component: Actions,
			name: 'actions'
		},
		{
			path: '/podcasts',
			component: Podcasts,
			name: 'podcasts',
		},
		{
			path: '/actions',
			component: Actions,
			name: 'actions',
		},
	],
})

router.afterEach((to) => {
	const rootTitle = to.meta.rootTitle?.(to)

	if (rootTitle) {
		document.title = `${rootTitle} - ${baseTitle}`
	} else {
		document.title = baseTitle
	}
})

export default router
