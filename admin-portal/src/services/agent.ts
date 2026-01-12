import { apiService } from './api'
import type { PaginatedResponse } from '@/types/api'

export interface CompanyAgent {
    id: number
    company_id: number
    did_id: number
    admin_voice_id: number
    name: string
    script: string
    status: 'request' | 'training' | 'active' | 'maintenance' | 'inactive'
    quantity: number
    price_per_min: number
    start_date: string
    end_date?: string
    company?: any
    did?: any
    admin_voice?: any
}

export interface AssignAgentData {
    company_id: number
    did_id: number
    admin_voice_id: number
    name: string
    script?: string
    quantity?: number
    price_per_min: number
    start_date: string
    status?: string
}

export interface AgentFilters {
    search?: string
    status?: string
    page?: number
    per_page?: number
}

export const agentService = {
    async getAgents(params?: any) {
        return apiService.get<PaginatedResponse<CompanyAgent>>('/admin/company-agents', { params })
    },

    async getAgent(id: number) {
        return apiService.get<CompanyAgent>(`/admin/company-agents/${id}`)
    },

    async createAgent(data: AssignAgentData) {
        return apiService.post<CompanyAgent>('/admin/company-agents', data)
    },

    async updateAgent(id: number, data: Partial<AssignAgentData>) {
        return apiService.put<CompanyAgent>(`/admin/company-agents/${id}`, data)
    },

    async deleteAgent(id: number) {
        return apiService.delete(`/admin/company-agents/${id}`)
    },

    async approveAgent(id: number, status: 'active' | 'training') {
        return apiService.post(`/admin/company-agents/${id}/approve`, { status })
    }
}
