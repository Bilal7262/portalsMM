import { apiService } from './api'
import type { PaginatedResponse } from '@/types/api'

export interface Invoice {
    id: number
    invoice_number: string
    company_id: number
    company?: {
        id: number
        business_name: string
    }
    effective_from: string
    effective_to: string
    total_amount: number
    status: 'draft' | 'generated' | 'sent' | 'paid' | 'overdue' | 'cancelled'
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
