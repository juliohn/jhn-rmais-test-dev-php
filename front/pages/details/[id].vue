<script setup lang="ts">
interface Address {
  id: number;
  supplier_id: number;
  is_main: number;
  cep: string;
  street: string;
  number: string;
  complement: string | null;
  city: string;
  state: string;
  neighborhood: string;
  reference: string | null;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
}

interface Phone {
  id: number;
  supplier_id: number;
  number: string;
  is_main: number;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
}

interface Supplier {
  id: number;
  name: string;
  document: string;
  email: string;
  address: Address;
  phones: Phone[];
}

const route = useRoute()
const isLoading = ref(true)
const supplier = ref<Supplier | null>(null)
const error = ref<string | null>(null)

// Reutilizar as funções de formatação do index.vue
function formatPhoneNumber(phone: string | undefined) {
  if (!phone) return ''
  const numbers = phone.replace(/\D/g, '')
  if (numbers.length === 13) {
    return numbers.replace(/(\d{2})(\d{2})(\d{5})(\d{4})/, '+$1 ($2) $3-$4')
  }
  if (numbers.length === 11) {
    return numbers.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3')
  }
  return phone
}

function formatDocument(document: string | undefined): string {
  if (!document) return ''
  const numbers = document.replace(/\D/g, '')
  if (numbers.length === 14) {
    return numbers.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5')
  }
  if (numbers.length === 11) {
    return numbers.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
  }
  return document
}

// Atualizar a função formatPhoneNumber para usar o telefone principal
function getMainPhone(phones: Phone[] | undefined): string {
  if (!phones || phones.length === 0) return ''
  const mainPhone = phones.find(phone => phone.is_main === 1)
  return mainPhone ? mainPhone.number : phones[0].number
}

// Add this new function
function getDocumentType(document: string | undefined): string {
  if (!document) return ''
  const numbers = document.replace(/\D/g, '')
  return numbers.length === 11 ? 'Pessoa Física' : 'Pessoa Jurídica'
}

// Buscar dados do fornecedor
async function fetchSupplier() {
  try {
    isLoading.value = true
    const response = await $fetch<{ data: Supplier }>(`http://localhost/api/suppliers/${route.params.id}`)
    supplier.value = response.data
  } catch (e) {
    console.error('Erro ao buscar fornecedor:', e)
    error.value = 'Erro ao carregar os dados do fornecedor'
  } finally {
    isLoading.value = false
  }
}

// Carregar dados quando o componente for montado
onMounted(() => {
  fetchSupplier()
})
</script>

<template>
  <div class="max-w-4xl mx-auto p-4">
    <!-- Header com botão de voltar -->
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-800">Detalhes do Fornecedor</h1>
      <NuxtLink
        to="/"
        class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors"
      >
        Voltar
      </NuxtLink>
    </div>

    <!-- Loading state -->
    <div v-if="isLoading" class="p-4 text-center">
      <div class="animate-spin h-8 w-8 mx-auto border-4 border-blue-500 border-t-transparent rounded-full"></div>
      <p class="mt-2 text-gray-600">Carregando dados do fornecedor...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="p-4 bg-red-100 text-red-700 rounded-lg">
      {{ error }}
    </div>

    <!-- Supplier details -->
    <div v-else-if="supplier" class="bg-white shadow-lg rounded-lg overflow-hidden">
      <div class="p-6">
        <!-- ID e Nome -->
        <div class="mb-6">
          <h2 class="text-xl font-semibold text-gray-800 mb-2">
            #{{ supplier.id }} - {{ supplier.name }}
          </h2>
        </div>

        <!-- Grid de informações -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Documento -->
          <div class="border-b pb-4">
            <h3 class="text-sm font-medium text-gray-500 mb-1">Documento</h3>
            <p class="text-gray-800">{{ formatDocument(supplier.document) }}</p>
          </div>

          <!-- Tipo de Pessoa -->
          <div class="border-b pb-4">
            <h3 class="text-sm font-medium text-gray-500 mb-1">Tipo</h3>
            <p class="text-gray-800">{{ getDocumentType(supplier.document) }}</p>
          </div>

          <!-- Email -->
          <div class="border-b pb-4">
            <h3 class="text-sm font-medium text-gray-500 mb-1">Email</h3>
            <p class="text-gray-800">{{ supplier.email }}</p>
          </div>

          <!-- Lista de Telefones -->
          <div class="border-b pb-4 md:col-span-2">
            <h3 class="text-sm font-medium text-gray-500 mb-3">Telefones</h3>
            <div class="space-y-2">
              <div v-for="phone in supplier.phones" :key="phone.id" 
                   class="flex items-center gap-2 text-gray-800">
                <span class="text-gray-800">{{ formatPhoneNumber(phone.number) }}</span>
                <span v-if="phone.is_main" 
                      class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                  Principal
                </span>
              </div>
            </div>
          </div>

          <!-- Endereço Principal -->
          <div class="border-b pb-4 md:col-span-2">
            <h3 class="text-sm font-medium text-gray-500 mb-1">Endereço</h3>
            <p class="text-gray-800">
              {{ supplier.address.street }}, {{ supplier.address.number }}
              <template v-if="supplier.address.complement">, {{ supplier.address.complement }}</template>
            </p>
            <p class="text-gray-800">
              {{ supplier.address.neighborhood }} - {{ supplier.address.city }}/{{ supplier.address.state }}
            </p>
            <p class="text-gray-800">CEP: {{ supplier.address.cep }}</p>
          </div>
        </div>

        <!-- Ações -->
        <div class="mt-8 flex gap-4">
          <NuxtLink
            :to="`/edit/${supplier.id}`"
            class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors"
          >
            Editar Fornecedor
          </NuxtLink>
        </div>
      </div>
    </div>

    <!-- Not found state -->
    <div v-else class="p-4 text-center">
      <p class="text-gray-600">Fornecedor não encontrado</p>
    </div>
  </div>
</template>
