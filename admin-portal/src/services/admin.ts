import { apiService } from './api'

export interface Admin {
    id: number
    name: string
    email: string
    status: 'active' | 'inactive'
    created_at: string
    roles?: { id: number; name: string }[]
}

export const adminService = {
    getAdmins() {
        return apiService.get<Admin[]>('/admin/admins')
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
