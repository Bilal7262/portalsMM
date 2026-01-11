import { apiService } from './api'
import type { PaginatedResponse } from '@/types/api'

export interface Invoice {
    id: number
    company_did_id: number
    company_did?: {
        did: {
            did_number: string
        }
        company: {
            name: string
        }
    }
    effective_from: string
    effective_to: string
    total_minutes_consumption: number
    billed_amount: number
    status: 'draft' | 'paid' | 'overdue' | 'cancelled'
    created_at: string
}

export const invoiceService = {
    async getInvoices(params?: any) {
        return apiService.get<PaginatedResponse<Invoice>>('/admin/invoices', { params })
    },

    async getInvoice(id: number) {
        return apiService.get<Invoice>(`/admin/invoices/${id}`)
    },

    async updateInvoiceStatus(id: number, status: string) {
        return apiService.put<Invoice>(`/admin/invoices/${id}`, { status })
    }
}
