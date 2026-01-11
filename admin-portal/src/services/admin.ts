import { apiService } from './api'
import type { PaginatedResponse } from '@/types/api'

export interface Admin {
    id: number
    name: string
    email: string
    status: 'active' | 'inactive'
    created_at: string
    roles?: { id: number; name: string }[]
}

export const adminService = {
    async getAdmins(params?: any) {
        return apiService.get<PaginatedResponse<Admin>>('/admin/admins', { params })
    },

    getAdmin(id: number) {
        return apiService.get<Admin>(`/admin/admins/${id}`)
    },

    createAdmin(data: any) {
        return apiService.post<Admin>('/admin/admins', data)
    },

    updateAdmin(id: number, data: any) {
        return apiService.put<Admin>(`/admin/admins/${id}`, data)
    },

    deleteAdmin(id: number) {
        return apiService.delete(`/admin/admins/${id}`)
    }
}
