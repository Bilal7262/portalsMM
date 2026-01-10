import { apiService } from './api'

export interface DashboardStats {
    total_companies: number
    active_companies: number
    total_dids: number
    assigned_dids: number
    available_dids: number
    total_revenue: number
    total_calls: number
    recent_calls: any[] // We can define a Call interface later
}

export const reportService = {
    async getDashboardStats() {
        return apiService.get<DashboardStats>('/admin/reports')
    }
}
