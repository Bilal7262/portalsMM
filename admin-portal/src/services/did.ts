import { apiService } from './api'

export interface DID {
    id: number
    did_number: string
    status: 'available' | 'assigned'
    company_did?: {
        company_id: number
        company: {
            id: number
            name: string
        }
        price_per_min: number
    }
}

export interface DidFormData {
    did_number: string
    status: 'available' | 'assigned'
}

export interface AssignDidData {
    company_id: number
    did_id: number
    price_per_min: number
}

export const didService = {
    async getDids(params?: any) {
        return apiService.get<{ data: DID[]; meta: any }>('/admin/dids', { params })
    },

    async createDid(data: DidFormData) {
        return apiService.post<DID>('/admin/dids', data)
    },

    async updateDid(id: number, data: Partial<DidFormData>) {
        return apiService.put<DID>(`/admin/dids/${id}`, data)
    },

    async deleteDid(id: number) {
        return apiService.delete(`/admin/dids/${id}`)
    },

    // Company DID Assignment
    async assignDid(data: AssignDidData) {
        return apiService.post('/admin/company-dids', data)
    },

    async releaseDid(assignmentId: number) {
        return apiService.delete(`/admin/company-dids/${assignmentId}`)
    }
}
