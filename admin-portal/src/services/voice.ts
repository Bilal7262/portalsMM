import { apiService } from './api'

export interface AdminVoice {
    id: number
    name: string
    transcript?: string
    scene_prompt?: string
    ref_audio?: string
    ref_audio_in_system_message?: boolean
    chunk_method?: string
    chunk_max_word_num?: number
    chunk_max_num_turns?: number
    generation_chunk_buffer_size?: number
    temperature?: number
    top_k?: number
    top_p?: number
    ras_win_len?: number
    ras_win_max_num_repeat?: number
    seed?: number
    status: 'active' | 'inactive'
    created_at: string
    updated_at: string
}

export interface VoiceFilters {
    search?: string
    status?: string
    page?: number
    per_page?: number
}

export const voiceService = {
    getVoices(params?: VoiceFilters) {
        return apiService.get<any>('/voices', { params })
    },

    getVoice(id: number) {
        return apiService.get<AdminVoice>(`/voices/${id}`)
    },

    createVoice(data: Partial<AdminVoice>) {
        return apiService.post<AdminVoice>('/voices', data)
    },

    updateVoice(id: number, data: Partial<AdminVoice>) {
        return apiService.put<AdminVoice>(`/voices/${id}`, data)
    },

    deleteVoice(id: number) {
        return apiService.delete(`/voices/${id}`)
    }
}
