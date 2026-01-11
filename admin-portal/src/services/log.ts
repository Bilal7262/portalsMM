import { apiService } from './api'
import type { PaginatedResponse } from '@/types/api'

export interface ActivityLog {
    id: number
    admin_id: number
    activity_type: string
    activity_details: string
    activity_date: string
    admin?: { id: number; name: string }
}

export const logService = {
    async getLogs(params?: any) {
        return apiService.get<PaginatedResponse<ActivityLog>>('/admin/activity-logs', { params })
    }
}

