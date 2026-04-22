import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import HomeView from '../views/HomeView.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import ProfileView from '../views/ProfileView.vue'
import PlayView from '../views/PlayView.vue'
import LeaderboardView from '../views/LeaderboardView.vue'
import AchievementsView from '../views/AchievementsView.vue'
import StoreView from '../views/StoreView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/',               name: 'home',         component: HomeView },
    { path: '/login',          name: 'login',         component: LoginView },
    { path: '/register',       name: 'register',      component: RegisterView },
    { path: '/profile',        name: 'profile',       component: ProfileView,      meta: { requiresAuth: true } },
    { path: '/play/:slug',     name: 'play',          component: PlayView,         meta: { requiresAuth: true }, props: true },
    { path: '/leaderboard/:slug', name: 'leaderboard', component: LeaderboardView },
    { path: '/achievements',   name: 'achievements',  component: AchievementsView, meta: { requiresAuth: true } },
    { path: '/store',          name: 'store',         component: StoreView },
  ],
})

router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.name === 'leaderboard' && to.params.slug === 'connect4' && !auth.isLoggedIn) {
    return { name: 'login' }
  }

  if (to.meta.requiresAuth && !auth.isLoggedIn) {
    return { name: 'login' }
  }
})

export default router

