import { apiService } from './api'

export interface ActivityLog {
    id: number
    admin_id: number
    activity_type: string
    activity_details: string
    activity_date: string
    admin?: { id: number; name: string }
}

export interface PaginatedLogs {
    data: ActivityLog[]
    current_page: number
    last_page: number
    total: number
}

export const logService = {
    getLogs(params?: any) {
        return apiService.get<PaginatedLogs>('/admin/activity-logs', { params })
    }
}
