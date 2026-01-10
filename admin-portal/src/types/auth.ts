export interface User {
    id: number
    name: string
    email: string
    roles?: Role[]
    // Add other user fields as needed
}

export interface Role {
    id: number
    name: string
    display_name: string
}

export interface LoginCredentials {
    email: string
    password: string
}

export interface AuthState {
    user: User | null
    token: string | null
    isAuthenticated: boolean
    loading: boolean
    error: string | null
}
