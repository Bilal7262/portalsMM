import { apiService } from './api'
import type { PaginatedResponse } from '@/types/api'

export interface Company {
    id: number
    business_name: string
    email: string
    phone: string
    status: 'active' | 'inactive' | 'pending' | 'suspended'
    balance?: number
    created_at?: string
    users_count?: number
    dids_count?: number
}

export interface CompanyFormData {
    business_name: string
    email: string
    phone: string
    status: string
    password?: string
}

export const companyService = {
    async getCompanies(params?: any) {
        return apiService.get<PaginatedResponse<Company>>('/admin/companies', { params })
    },

    async getCompany(id: number) {
        return apiService.get<Company>(`/admin/companies/${id}`)
    },

    async createCompany(data: CompanyFormData) {
        return apiService.post<Company>('/admin/companies', data)
    },

    async updateCompany(id: number, data: Partial<CompanyFormData>) {
        return apiService.put<Company>(`/admin/companies/${id}`, data)
    },

    async deleteCompany(id: number) {
        return apiService.delete(`/admin/companies/${id}`)
    }
}
