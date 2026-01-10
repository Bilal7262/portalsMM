import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { apiService } from '@/services/api'
import type { LoginCredentials, User, AuthState } from '@/types/auth' // We'll define these types next

export const useAuthStore = defineStore('auth', () => {
    // State
    const user = ref<User | null>(null)
    const token = ref<string | null>(localStorage.getItem('auth_token'))
    const isAuthenticated = computed(() => !!token.value)
    const loading = ref(false)
    const error = ref<string | null>(null)

    // Actions
    async function login(credentials: LoginCredentials, portal: 'admin' | 'company' = 'admin') {
        loading.value = true
        error.value = null
        try {
            // 1. Get CSRF cookie (Sanctum) - Optional if using token based, but good for SPA
            // await apiService.get('/sanctum/csrf-cookie') 

            // 2. Perform Login
            const endpoint = portal === 'admin' ? '/admin/login' : '/company/login'
            const response = await apiService.post<{ token: string; user?: User }>(endpoint, credentials)

            // Store Token
            token.value = response.token
            localStorage.setItem('auth_token', response.token)

            // 3. Fetch User Details immediately if not returned in login
            if (response.user) {
                user.value = response.user
            } else {
                await fetchUser(portal)
            }

            return true
        } catch (err: any) {
            error.value = err.message || 'Login failed'
            return false
        } finally {
            loading.value = false
        }
    }

    async function fetchUser(portal: 'admin' | 'company' = 'admin') {
        if (!token.value) return

        try {
            const endpoint = portal === 'admin' ? '/admin/me' : '/company/me'
            const userData = await apiService.get<User>(endpoint)
            user.value = userData
        } catch (err) {
            // If fetch user fails (e.g. 401), logout
            logout()
        }
    }

    async function logout(portal: 'admin' | 'company' = 'admin') {
        if (token.value) {
            try {
                const endpoint = portal === 'admin' ? '/admin/logout' : '/company/logout'
                await apiService.post(endpoint)
            } catch (err) {
                console.error('Logout API call failed', err)
            }
        }

        // Clear State
        user.value = null
        token.value = null
        localStorage.removeItem('auth_token')
    }

    // Test Authentication / Check Status
    async function checkAuth() {
        if (!token.value) return false
        await fetchUser() // Defaults to admin for now, logic might need adjustment for mixed portals
        return isAuthenticated.value
    }

    return {
        user,
        token,
        isAuthenticated,
        loading,
        error,
        login,
        logout,
        fetchUser,
        checkAuth
    }
})
