<script setup lang="ts">
import { navigateTo } from 'nuxt/app'

interface Supplier {
  id: number;
  name: string;
  document: string;
  email: string;
  phone: string;
}

interface Toast {
  id: number;
  message: string;
  type: 'success' | 'error';
}

// Estado de loading
const isLoading = ref(true)
const suppliers = ref<Supplier[]>([])

// Adicionar refs para controle do modal
const showDeleteModal = ref(false)
const supplierToDelete = ref<Supplier | null>(null)

// Adicionar ref para controle do loading
const isDeleting = ref(false)

// Adicionar estado para os toasts
const toasts = ref<Toast[]>([])
let toastId = 0

// Adicionar ref para pesquisa
const searchTerm = ref('')

// Adicionar refs para paginação
const currentPage = ref(1)
const totalPages = ref(1)
const perPage = ref(10)

// Adicionar refs para ordenação
const sortBy = ref('name')
const sortDirection = ref('asc')

// Adicionar ref para o input de pesquisa
const searchInput = ref<HTMLInputElement | null>(null)

// Modificar interface para incluir total
interface ApiResponse {
  data: {
    data: Supplier[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  }
}

// Adicionar ref para total de registros
const totalRecords = ref(0)

// Função para adicionar toast
function addToast(message: string, type: 'success' | 'error') {
  const id = toastId++
  toasts.value.push({ id, message, type })
  
  // Remove o toast após 3 segundos
  setTimeout(() => {
    removeToast(id)
  }, 3000)
}

// Função para remover toast
function removeToast(id: number) {
  toasts.value = toasts.value.filter(t => t.id !== id)
}

// Função para buscar fornecedores
async function fetchSuppliers(search: string = '') {
  try {
    isLoading.value = true
    const response = await $fetch<ApiResponse>('/api/suppliers', {
      baseURL: 'http://localhost',
      params: {
        page: currentPage.value,
        per_page: perPage.value,
        sort_by: sortBy.value,
        sort_direction: sortDirection.value,
        ...(search ? { search } : {})
      }
    })
    
    suppliers.value = response.data.data
    currentPage.value = response.data.current_page
    totalPages.value = response.data.last_page
    perPage.value = response.data.per_page
    totalRecords.value = response.data.total

    // Restaurar foco ao input de pesquisa se houver texto
    if (searchTerm.value) {
      nextTick(() => {
        searchInput.value?.focus()
      })
    }
  } catch (error) {
    addToast('Erro ao carregar fornecedores', 'error')
  } finally {
    isLoading.value = false
  }
}

// Buscar dados quando o componente for montado
onMounted(() => {
  fetchSuppliers()
})

// Função para formatar número de telefone
function formatPhoneNumber(phone: string) {
  // Remove todos os caracteres não numéricos
  const numbers = phone.replace(/\D/g, '')
  
  // Verifica se é um número brasileiro completo
  if (numbers.length === 13) { // Incluindo +55
    return numbers.replace(/(\d{2})(\d{2})(\d{5})(\d{4})/, '+$1 ($2) $3-$4')
  }
  // Caso seja um número sem o +55
  if (numbers.length === 11) {
    return numbers.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3')
  }
  // Retorna o número original se não corresponder aos padrões
  return phone
}

// Função para formatar documento (CPF ou CNPJ)
function formatDocument(document: string): string {
  // Remove todos os caracteres não numéricos
  const numbers = document.replace(/\D/g, '')
  
  // Verifica se é CNPJ (14 dígitos)
  if (numbers.length === 14) {
    return numbers.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5')
  }
  // Verifica se é CPF (11 dígitos)
  if (numbers.length === 11) {
    return numbers.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
  }
  // Retorna o documento original se não corresponder aos padrões
  return document
}

// Função para abrir o modal de confirmação
function openDeleteModal(supplier: Supplier) {
  supplierToDelete.value = supplier
  showDeleteModal.value = true
}

// Função para confirmar exclusão
async function confirmDelete() {
  if (!supplierToDelete.value) return
  
  isDeleting.value = true
  
  try {
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    await $fetch(`http://localhost/api/suppliers/${supplierToDelete.value.id}`, {
      method: 'DELETE'
    })
    await fetchSuppliers()
    showDeleteModal.value = false
    addToast('Fornecedor excluído com sucesso!', 'success')
  } catch (error) {
    addToast('Erro ao excluir fornecedor. Tente novamente.', 'error')
  } finally {
    isDeleting.value = false
  }
}

// Adicionar função para mudar de página
function changePage(page: number) {
  currentPage.value = page
  fetchSuppliers(searchTerm.value)
}

// Adicionar função para alterar ordenação
function changeSort(column: string) {
  if (sortBy.value === column) {
    // Se clicar na mesma coluna, inverte a direção
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    // Se clicar em uma coluna diferente, define ela como 'asc'
    sortBy.value = column
    sortDirection.value = 'asc'
  }
  fetchSuppliers(searchTerm.value)
}

// Adicionar função para navegação
function goToDetails(id: number) {
  navigateTo(`/details/${id}`);
}

</script>


<template>
    <!-- Seção de pesquisa e botões -->
    <div class="mb-4 flex flex-col sm:flex-row gap-4 items-center w-full">
        <!-- Barra de pesquisa (removida condição v-if) -->
        <div class="relative flex-1">
            <input
                ref="searchInput"
                v-model="searchTerm"
                type="text"
                placeholder="Pesquisar fornecedores..."
                :key="'search-input'"
                @keyup.enter="fetchSuppliers(searchTerm)"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
        </div>
        <!-- Botões (sempre visíveis) -->
        <div class="flex gap-2">
            <button
                @click="fetchSuppliers(searchTerm)"
                class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors flex items-center"
            >
                {{ searchTerm ? 'Pesquisar' : 'Atualizar' }}
            </button>
            <NuxtLink
                to="/new"
                class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition-colors flex items-center"
            >
                Novo Fornecedor
            </NuxtLink>
        </div>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg w-full">
        <!-- Loading state -->
        <div v-if="isLoading" class="p-4 text-center">
            <div class="animate-spin h-8 w-8 mx-auto border-4 border-blue-500 border-t-transparent rounded-full"></div>
            <p class="mt-2 text-gray-600">Carregando fornecedores...</p>
        </div>

        <!-- Table content -->
        <div v-else class="w-full text-sm text-gray-500">
            <!-- Empty state message -->
            <div v-if="suppliers.length === 0" class="p-8 text-center">
                <p class="text-gray-600 text-lg">Nenhum fornecedor encontrado</p>
            </div>

            <!-- Table content (only show if there are suppliers) -->
            <template v-else>
                <div class="w-full"> <!-- Removido min-w fixo -->
                    <!-- Header -->
                    <div class="hidden md:flex text-xs text-gray-700 uppercase bg-gray-50">
                        <div @click="changeSort('id')" 
                             class="px-6 py-3 w-[80px] text-center cursor-pointer hover:bg-gray-100">
                            ID
                            <span v-if="sortBy === 'id'" class="ml-1">
                                {{ sortDirection === 'asc' ? '↑' : '↓' }}
                            </span>
                        </div>
                        <div @click="changeSort('name')" 
                             class="px-6 py-3 w-[600px] text-center cursor-pointer hover:bg-gray-100">
                            Nome
                            <span v-if="sortBy === 'name'" class="ml-1">
                                {{ sortDirection === 'asc' ? '↑' : '↓' }}
                            </span>
                        </div>
                        <div class="px-6 py-3 w-[180px] text-center">Documento</div>
                        <div @click="changeSort('email')" 
                             class="px-6 py-3 w-[250px] text-center cursor-pointer hover:bg-gray-100">
                            Email
                            <span v-if="sortBy === 'email'" class="ml-1">
                                {{ sortDirection === 'asc' ? '↑' : '↓' }}
                            </span>
                        </div>
                        <div class="px-6 py-3 w-[180px] text-center">Telefone</div>
                        <div class="px-6 py-3 w-[220px] text-center">Ações</div>
                    </div>
                    <!-- Rows -->
                    <div v-for="(supplier, index) in suppliers" 
                         :key="supplier.id" 
                         :class="{'bg-gray-50': index % 2 === 0, 'bg-white': index % 2 === 1}"
                         class="flex flex-col md:flex-row border-b hover:bg-gray-100 cursor-pointer"
                         @click="goToDetails(supplier.id)">
                        <div class="px-6 py-4 flex md:hidden font-bold">ID:</div>
                        <div class="px-6 py-4 w-[80px] truncate text-center">{{ supplier.id }}</div>
                        
                        <div class="px-6 py-4 flex md:hidden font-bold">Nome:</div>
                        <div class="px-6 py-4 w-[600px] truncate text-center">{{ supplier.name }}</div>
                        
                        <div class="px-6 py-4 flex md:hidden font-bold">Documento:</div>
                        <div class="px-6 py-4 w-[180px] truncate">{{ formatDocument(supplier.document) }}</div>
                        
                        <div class="px-6 py-4 flex md:hidden font-bold">Email:</div>
                        <div class="px-6 py-4 w-[250px] truncate text-center">{{ supplier.email }}</div>
                        
                        <div class="px-6 py-4 flex md:hidden font-bold">Telefone:</div>
                        <div class="px-6 py-4 w-[180px] truncate text-center">{{ formatPhoneNumber(supplier.phone) }}</div>
                        
                        <div class="px-6 py-4 flex md:hidden font-bold">Ações:</div>
                        <div class="px-6 py-4 w-[220px] space-x-2" @click.stop>
                            <NuxtLink 
                                :to="`/edit/${supplier.id}`"
                                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors inline-block">
                                Editar
                            </NuxtLink>
                            <button 
                                @click="openDeleteModal(supplier)"
                                class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors">
                                Excluir
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Modal de confirmação -->
    <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-bold mb-4">Confirmar exclusão</h3>
        <p class="mb-2">Deseja realmente excluir este fornecedor?</p>
        <div class="mb-4 text-sm text-gray-600">
          <p><strong>Nome:</strong> {{ supplierToDelete?.name }}</p>
          <p><strong>Email:</strong> {{ supplierToDelete?.email }}</p>
        </div>
        <div class="flex justify-end space-x-3">
          <button 
            @click="showDeleteModal = false"
            :disabled="isDeleting"
            class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition-colors disabled:opacity-50">
            Cancelar
          </button>
          <button 
            @click="confirmDelete"
            :disabled="isDeleting"
            class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors disabled:opacity-50 flex items-center">
            <span v-if="isDeleting" class="animate-spin h-4 w-4 mr-2 border-2 border-white border-t-transparent rounded-full"></span>
            {{ isDeleting ? 'Excluindo...' : 'Confirmar' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Adicionar componente Toast -->
    <Toast
      :toasts="toasts"
      @remove="removeToast"
    />

    <!-- Substituir a paginação anterior por esta nova versão -->
    <div v-if="totalPages > 1" class="flex justify-between items-center p-4 border-t">
      <!-- Informações sobre os registros -->
      <div class="text-sm text-gray-600">
        Mostrando {{ ((currentPage - 1) * perPage) + 1 }} até 
        {{ Math.min(currentPage * perPage, totalRecords) }} 
        de {{ totalRecords }} registros
      </div>

      <!-- Botões de paginação -->
      <div class="flex gap-2">
        <!-- Botão Anterior -->
        <button
          @click="changePage(currentPage - 1)"
          :disabled="currentPage === 1"
          class="px-4 py-2 border rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:hover:bg-white"
        >
          Anterior
        </button>

        <!-- Números das páginas -->
        <div class="flex gap-1">
          <!-- Primeira página -->
          <button
            v-if="currentPage > 3"
            @click="changePage(1)"
            class="px-4 py-2 border rounded-lg hover:bg-gray-100"
          >
            1
          </button>

          <!-- Ellipsis início -->
          <span v-if="currentPage > 3" class="px-4 py-2">...</span>

          <!-- Páginas ao redor da atual -->
          <button
            v-for="page in totalPages"
            :key="page"
            @click="changePage(page)"
            v-show="page >= currentPage - 1 && page <= currentPage + 1 && page > 0 && page <= totalPages"
            :class="[
              'px-4 py-2 border rounded-lg',
              currentPage === page
                ? 'bg-blue-500 text-white hover:bg-blue-600'
                : 'hover:bg-gray-100'
            ]"
          >
            {{ page }}
          </button>

          <!-- Ellipsis fim -->
          <span v-if="currentPage < totalPages - 2" class="px-4 py-2">...</span>

          <!-- Última página -->
          <button
            v-if="currentPage < totalPages - 2"
            @click="changePage(totalPages)"
            class="px-4 py-2 border rounded-lg hover:bg-gray-100"
          >
            {{ totalPages }}
          </button>
        </div>

        <!-- Botão Próxima -->
        <button
          @click="changePage(currentPage + 1)"
          :disabled="currentPage === totalPages"
          class="px-4 py-2 border rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:hover:bg-white"
        >
          Próxima
        </button>
      </div>
    </div>

</template>

<style scoped>
</style>