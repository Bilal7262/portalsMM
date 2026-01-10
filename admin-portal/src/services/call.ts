export interface Call {
    id: number
    user_phone: string
    disposition: string
    duration: number
    created_at: string
    invoice?: {
        company_did?: {
            company?: {
                name: string
            }
            did?: {
                did_number: string
            }
        }
    }
}

import { apiService } from './api'

export const callService = {
    async getCalls(params: any = {}) {
        return apiService.get('/admin/calls', { params })
    },

    async getCall(id: number | string) {
        return apiService.get<Call>(`/admin/calls/${id}`)
    }
}
