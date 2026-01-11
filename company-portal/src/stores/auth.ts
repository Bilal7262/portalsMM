import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/lib/axios'
import { useRouter } from 'vue-router'

export const useAuthStore = defineStore('auth', () => {
    const user = ref<any>(null)
    const token = ref<string | null>(localStorage.getItem('token'))
    const router = useRouter()

    const isAuthenticated = computed(() => !!token.value)

    async function login(credentials: any) {
        try {
            const response = await api.post('/company/login', credentials)
            token.value = response.data.token
            user.value = response.data.user
            localStorage.setItem('token', token.value!)

            // Redirect or Return
            return true
        } catch (error) {
            console.error('Login failed', error)
            throw error
        }
    }

    async function logout() {
        try {
            await api.post('/company/logout')
        } catch (e) {
            // Ignore error on logout
        } finally {
            token.value = null
            user.value = null
            localStorage.removeItem('token')
            // router.push('/login') // Let component handle redirect if needed
        }
    }

    async function fetchUser() {
        if (!token.value) return
        try {
            const response = await api.get('/company/me')
            user.value = response.data
        } catch (error) {
            logout()
        }
    }

    return {
        user,
        token,
        isAuthenticated,
        login,
        logout,
        fetchUser
    }
})
