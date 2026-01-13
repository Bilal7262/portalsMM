<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-2xl font-bold tracking-tight">Companies</h2>
      <button 
        @click="openAddModal"
        class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
      >
        <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
        Add Company
      </button>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-4 bg-white p-4 shadow sm:rounded-lg">
      <div class="flex-1">
        <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
        <input
          v-model="filters.search"
          type="text"
          id="search"
          placeholder="Search by name or email..."
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm h-10 px-3"
          @input="handleSearch"
        />
      </div>
      <div class="w-full sm:w-48">
        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
        <select
          v-model="filters.status"
          id="status"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm h-10 px-3"
          @change="fetchCompanies(1)"
        >
          <option value="">All Statuses</option>
          <option value="active">Active</option>
          <option value="pending">Pending</option>
          <option value="inactive">Inactive</option>
          <option value="suspended">Suspended</option>
        </select>
      </div>
    </div>

    <div class="bg-white shadow sm:rounded-lg overflow-hidden relative">
      <div v-if="loading" class="absolute inset-0 bg-white/50 flex items-center justify-center z-10">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      </div>

      <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
          <tr>
            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Business Name</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Email</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Phone</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-for="company in companies" :key="company.id">
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">{{ company.business_name }}</td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ company.email }}</td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ company.phone }}</td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
               <span :class="[
                  company.status === 'active' ? 'bg-green-100 text-green-800 border-green-200' : 
                  company.status === 'pending' ? 'bg-yellow-100 text-yellow-800 border-yellow-200' :
                  'bg-gray-100 text-gray-800 border-gray-200',
                  'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium border'
                ]">
                {{ company.status }}
              </span>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 space-x-3">
              <button 
                v-if="company.status === 'pending'"
                @click="approveCompany(company)"
                class="text-indigo-600 hover:text-indigo-900"
                title="Approve"
              >
                <Check class="w-5 h-5" />
              </button>
              <button 
                @click="openEditModal(company)"
                class="text-blue-600 hover:text-blue-900"
                title="Edit"
              >
                <Edit class="w-5 h-5" />
              </button>
              <button 
                @click="deleteCompany(company)"
                class="text-red-600 hover:text-red-900"
                title="Delete"
              >
                <Trash2 class="w-5 h-5" />
              </button>
            </td>
          </tr>
          <tr v-if="companies.length === 0 && !loading">
            <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
              No companies found matching your criteria.
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <Pagination 
        v-if="meta.total > 0"
        :meta="meta"
        @page-change="fetchCompanies"
      />
    </div>


    <CompanyModal 
      :open="isCompanyModalOpen"
      :company="editingCompany"
      @close="isCompanyModalOpen = false"
      @saved="fetchCompanies(meta.current_page)"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { Plus, Edit, Trash2, Check } from 'lucide-vue-next'
const PlusIcon = Plus
import { companyService } from '@/services/company'
import Pagination from '@/components/ui/Pagination.vue'
import CompanyModal from '@/components/modals/CompanyModal.vue'

const companies = ref<any[]>([])
const loading = ref(false)
const meta = ref({
  current_page: 1,
  last_page: 1,
  from: 1,
  to: 1,
  total: 0
})

const filters = reactive({
  search: '',
  status: ''
})

const isCompanyModalOpen = ref(false)
const editingCompany = ref<any | null>(null)

const fetchCompanies = async (page = 1) => {
    loading.value = true
    try {
        const response = await companyService.getCompanies({
            page,
            search: filters.search,
            status: filters.status
        })
        companies.value = response.data
        meta.value = {
          current_page: response.current_page,
          last_page: response.last_page,
          from: response.from,
          to: response.to,
          total: response.total
        }
    } catch (error) {
        console.error('Failed to fetch companies:', error)
    } finally {
        loading.value = false
    }
}

// Simple debounce
let timeout: any
const handleSearch = () => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
        fetchCompanies(1)
    }, 500)
}

const approveCompany = async (company: any) => {
    try {
        await companyService.updateCompany(company.id, { status: 'active' })
        company.status = 'active'
    } catch (error) {
        console.error('Failed to approve company:', error)
    }
}

const openAddModal = () => {
  editingCompany.value = null
  isCompanyModalOpen.value = true
}

const openEditModal = (company: any) => {
  editingCompany.value = company
  isCompanyModalOpen.value = true
}

const deleteCompany = async (company: any) => {
  if (!confirm(`Are you sure you want to delete ${company.business_name}? All related data will be removed.`)) return
  
  try {
    await companyService.deleteCompany(company.id)
    fetchCompanies(meta.value.current_page)
  } catch (error) {
    console.error('Failed to delete company:', error)
    alert('Failed to delete company.')
  }
}

onMounted(() => {

    fetchCompanies()
})
</script>



