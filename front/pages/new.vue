<script setup lang="ts">
import { ref, watch } from 'vue'

interface Supplier {
  id?: number
  name: string
  document_type: 'F' | 'J'
  document: string
  email: string
  address: {
    cep: string
    street: string
    number: string
    complement?: string
    supplier_id: string
    city: string
    state: string
  }
  phones: {
    number: string
    is_main: boolean
  }[]
}

interface Toast {
  id: number
  message: string
  type: 'success' | 'error'
}

interface Errors {
  document?: string
  name?: string
  email?: string
  address: {
    cep?: string
    street?: string
    number?: string
    neighborhood?: string
    city?: string
    state?: string
  }
  phones: Array<{ number?: string }> |string[]
}

// Adicionar interface para resposta da API do CNPJ
interface CNPJResponse {
  razao_social: string
  nome_fantasia: string
  cnpj: string
  cep: string
  // ... outros campos que você precise
}

// Definir os refs com suas tipagens
const form = ref<Supplier>({
  document_type: 'J',
  document: '',
  name: '',
  email: '',
  address: {
    cep: '',
    street: '',
    number: '',
    complement: '',
    neighborhood: '',
    city: '',
    state: ''
  },
  phones: [
    {
      number: '',
      is_main: 1
    }
  ]
})

const errors = ref<Errors>({
  address: {},
  phones: []
})

const loading = ref(false)
const cnpjData = ref<CNPJResponse | null>(null)
const error = ref(null)
const loadingCep = ref(false)
const toasts = ref<Toast[]>([])
let toastId = 0

const clearMessages = () => {
  error.value = null
  cnpjData.value = null
}

const formatDocument = (value: string) => {
  // Limpa caracteres não numéricos
  const numbers = value.replace(/\D/g, '')
  
  if (form.value.document_type === 'F') {
    // Formato: 000.000.000-00
    return numbers
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d{1,2})$/, '$1-$2')
      .slice(0, 14)
  } else {
    // Formato: 00.000.000/0000-00
    return numbers
      .replace(/(\d{2})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d)/, '$1/$2')
      .replace(/(\d{4})(\d{1,2})$/, '$1-$2')
      .slice(0, 18)
  }
}

// Atualizar o watch para o document_type
watch(() => form.value.document_type, () => {
  form.value.document = ''
  clearMessages()
})

const validateCNPJ = async (cnpj: string) => {
  if (form.value.document_type !== 'J') return
  
  clearMessages()

  const cleanCNPJ = cnpj.replace(/[^0-9]/g, '')
  if (cleanCNPJ.length !== 14) return

  loading.value = true

  try {
    const timeoutPromise = new Promise((_, reject) => {
      setTimeout(() => reject(new Error('Timeout')), 5000)
    })

    // Fazer a requisição
    const fetchPromise = fetch(`https://brasilapi.com.br/api/cnpj/v1/${cleanCNPJ}`)

    // Competir entre o timeout e a requisição
    const response = await Promise.race([fetchPromise, timeoutPromise])
    const data = await response.json() as CNPJResponse
    
    if (response.ok) {
      cnpjData.value = data
      form.value.name = data.razao_social

      form.value.address.cep = data.cep
      searchCep(data.cep)
      
      error.value = null
    } else {
      error.value = 'CNPJ não encontrado ou inválido'
      cnpjData.value = null
    }
  } catch (err) {
    if (err instanceof Error) {
      error.value = err.message === 'Timeout' 
        ? 'Não foi possível validar o CNPJ. Continue o cadastro manualmente.'
        : 'Erro ao validar CNPJ. Continue o cadastro manualmente.'
    }
    cnpjData.value = null
  } finally {
    loading.value = false
  }
}

// Criar um tipo para os campos básicos do Supplier
type BasicFields = 'name' | 'email' | 'document'

// Criar um tipo para os campos do endereço
type AddressFields = 'cep' | 'street' | 'number' | 'neighborhood' | 'city' | 'state'

const validateField = (field: BasicFields | AddressFields, section: 'address' | null = null) => {
  if (section === 'address') {
    if (!errors.value.address) {
      errors.value.address = {}
    }
    const value = form.value.address[field as AddressFields]
    errors.value.address[field as AddressFields] = !value || 
      (typeof value === 'string' && value.trim() === '')
        ? 'Este campo é obrigatório' 
        : ''
  } else {
    const value = form.value[field as BasicFields]
    errors.value[field as BasicFields] = !value || 
      (typeof value === 'string' && value.trim() === '')
        ? 'Este campo é obrigatório' 
        : ''
  }
}

const validateEmail = () => {
  if (form.value.email.trim() === '') {
    errors.value.email = 'Este campo é obrigatório'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
    errors.value.email = 'Email inválido'
  } else {
    errors.value.email = ''
  }
}

const formatCep = (value: string) => {
  // Remove caracteres não numéricos
  const numbers = value.replace(/\D/g, '')
  // Aplica a máscara: 00000-000
  return numbers
    .replace(/(\d{5})(\d)/, '$1-$2')
    .slice(0, 9)
}

// Interface para resposta da API de CEP
interface CepResponse {
  cep: string
  street: string
  neighborhood: string
  city: string
  state: string
}

const searchCep = async (cep: string) => {
  const cleanCep = cep.replace(/\D/g, '')
  if (cleanCep.length !== 8) return

  loadingCep.value = true
  errors.value.address.cep = 'Pesquisando CEP...'

  try {
    const timeoutPromise = new Promise((_, reject) => {
      setTimeout(() => reject(new Error('Timeout')), 5000)
    })

    const fetchPromise = fetch(`https://brasilapi.com.br/api/cep/v2/${cleanCep}`)
    const response = await Promise.race([fetchPromise, timeoutPromise])
    const data = await response.json() as CepResponse

    if (response.ok) {
      form.value.address.street = data.street
      form.value.address.neighborhood = data.neighborhood
      form.value.address.city = data.city
      form.value.address.state = data.state
      errors.value.address.cep = ''
    } else {
      errors.value.address.cep = 'CEP não encontrado, por favor continue manualmente'
    }
  } catch (err) {
    if (err instanceof Error) {
      errors.value.address.cep = err.message === 'Timeout'
        ? 'CEP não encontrado, por favor continue manualmente'
        : 'CEP não encontrado, por favor continue manualmente'
    }
  } finally {
    loadingCep.value = false
  }
}

const addPhone = () => {
  if (form.value.phones.length < 3) {
    form.value.phones.push({
      number: '',
      is_main: 0
    })
  }
}

const setMainPhone = (index: number): void => {
  form.value.phones.forEach((phone, i) => {
    phone.is_main = i === index ? 1 : 0
  })
}

const removePhone = (index: number): void => {
  const wasMain = form.value.phones[index].is_main === 1
  form.value.phones.splice(index, 1)
  
  if (wasMain && form.value.phones.length > 0) {
    form.value.phones[0].is_main = 1
  }
}

const formatDDI = (value: string): string => {
  return value.replace(/\D/g, '').slice(0, 3)
}

const formatPhone = (value: string): string => {
  let numbers = value.replace(/^\+55\s?/, '').replace(/\D/g, '')
  
  if (numbers.length > 11) {
    numbers = numbers.slice(-11)
  }
  
  if (numbers.length <= 10) {
    return numbers ? '+55 ' + numbers
      .replace(/^(\d{2})/, '($1) ')
      .replace(/(\d{4})(\d)/, '$1-$2') : ''
  } else {
    return numbers ? '+55 ' + numbers
      .replace(/^(\d{2})/, '($1) ')
      .replace(/(\d{5})(\d)/, '$1-$2') : ''
  }
}

const cleanPhoneNumber = (number: string): string => {
  return number.replace(/^\+55\s?/, '').replace(/\D/g, '')
}

const validateForm = () => {
  let isValid = true

  // Validar campos básicos
  validateField('name')
  validateField('email')
  validateField('document')
  
  // Validar campos de endereço
  validateField('cep', 'address')
  validateField('street', 'address')
  validateField('number', 'address')
  validateField('neighborhood', 'address')
  validateField('city', 'address')
  validateField('state', 'address')

  // Validar telefones
  if (form.value.phones.length === 0) {
    errors.value.phones = [{ number: 'É necessário adicionar pelo menos um telefone' }]
    isValid = false
  } else {
    errors.value.phones = form.value.phones.map(phone => ({
      number: !phone.number.trim() ? 'Número de telefone é obrigatório' : undefined
    }))
    
    if (errors.value.phones.some(error => error.number)) {
      isValid = false
    }
  }

  const hasBasicErrors = Object.entries(errors.value)
    .filter(([key]) => key !== 'address' && key !== 'phones')
    .some(([_, value]) => typeof value === 'string' ? value.length > 0 : value !== undefined)

  const hasAddressErrors = Object.values(errors.value.address)
    .some(error => typeof error === 'string' && error.length > 0)

  const hasPhoneErrors = errors.value.phones
    .some(error => error.number !== undefined)

  return isValid && !hasBasicErrors && !hasAddressErrors && !hasPhoneErrors
}

function addToast(message: string, type: 'success' | 'error') {
  const id = toastId++
  toasts.value.push({ id, message, type })
  setTimeout(() => {
    removeToast(id)
  }, 3000)
}

function removeToast(id: number) {
  toasts.value = toasts.value.filter(t => t.id !== id)
}

const submitForm = async () => {
  try {
    if (!validateForm()) {
      return
    }

    loading.value = true

    // Preparar os dados para envio
    const formData = {
      ...form.value,
      document: form.value.document.replace(/\D/g, ''),
      phones: form.value.phones.map(phone => ({
        ...phone,
        number: cleanPhoneNumber(phone.number)
      }))
    }

    // Enviar dados usando $fetch do Nuxt
    const response = await $fetch<{ data: Supplier, message: string }>('http://localhost/api/suppliers', {
      method: 'POST',
      body: formData
    }).catch(error => {
      // Handle validation error (422)
      if (error.response?.status === 422) {
        const firstError = Object.values(error.response._data);
        throw new Error(firstError[0] || 'Erro de validação')
      }
      throw error
    })

    // Aguardar 2 segundos antes de mostrar o toast
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    // Mostrar toast de sucesso
    addToast(response.message || 'Fornecedor cadastrado com sucesso!', 'success')

    // Reset do formulário
    resetForm()

    // Redirecionar após 1 segundo
    setTimeout(() => {
      navigateTo('/')
    }, 1000)

  } catch (error) {
    addToast(error.message || 'Erro ao cadastrar fornecedor', 'error')
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  form.value = {
    document_type: 'J',
    document: '',
    name: '',
    email: '',
    address: {
      cep: '',
      street: '',
      number: '',
      complement: '',
      neighborhood: '',
      city: '',
      state: ''
    },
    phones: [
      {
        number: '',
        is_main: 1
      }
    ]
  }
  clearMessages()
}
</script>

<template>
  <div class="container mx-auto p-4 relative">
    <!-- Loading Overlay -->
    <div v-if="loading" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
      <div class="bg-white p-6 rounded-lg shadow-lg text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
        <p class="text-gray-700">Processando...</p>
      </div>
    </div>

    <!-- Replace old toast with Toast component -->
    <Toast
      :toasts="toasts"
      @remove="removeToast"
    />

    <h1 class="text-2xl font-bold mb-4">Cadastro de Fornecedor</h1>
    
    <div class="flex flex-col lg:flex-row gap-4">
      <!-- Dados Principais -->
      <div class="w-full lg:w-1/3">
        <div class="border rounded-lg p-6 bg-white shadow-md">
          <h2 class="text-xl font-semibold mb-4">Dados Principais</h2>
          
          <!-- Tipo de Documento -->
          <div class="mb-4">
            <label class="block mb-2">Tipo de Documento</label>
            <div class="flex gap-4">
              
              <label class="inline-flex items-center">
                <input 
                  type="radio" 
                  v-model="form.document_type" 
                  value="J"
                  class="form-radio"
                >
                <span class="ml-2">CNPJ</span>
              </label>
              <label class="inline-flex items-center">
                <input 
                  type="radio" 
                  v-model="form.document_type" 
                  value="F"
                  class="form-radio"
                >
                <span class="ml-2">CPF</span>
              </label>
            </div>
          </div>

          <!-- Documento -->
          <div class="mb-4">
            <label class="block mb-2">Documento *</label>
            <input 
              v-model="form.document"
              type="text"
              class="w-full p-2 border rounded"
              :class="{'border-red-500': errors.document}"
              required
              @input="(e) => form.document = formatDocument((e.target as HTMLInputElement).value)"
              @blur="form.document_type === 'J' && validateCNPJ(form.document)"
              :maxlength="form.document_type === 'F' ? 14 : 18"
              :placeholder="form.document_type === 'F' ? '000.000.000-00' : '00.000.000/0000-00'"
            >
            <div v-if="errors.document" class="text-red-500 text-sm mt-1">
              {{ errors.document }}
            </div>
            <div v-if="loading" class="text-gray-600 text-sm mt-1">
              Validando CNPJ...
            </div>
            <div v-if="error" class="text-red-500 text-sm mt-1">
              {{ error }}
            </div>
            <div v-if="cnpjData && cnpjData.razao_social && form.document_type === 'J'" class="text-green-600 text-sm mt-1">
              CNPJ válido - {{ cnpjData.razao_social }}
            </div>
          </div>

          <!-- Nome -->
          <div class="mb-4">
            <label class="block mb-2">Nome</label>
            <input 
              v-model="form.name"
              type="text"
              class="w-full p-2 border rounded"
              :class="{'border-red-500': errors.name}"
              required
              @blur="validateField('name')"
            >
            <div v-if="errors.name" class="text-red-500 text-sm mt-1">
              {{ errors.name }}
            </div>
          </div>

          <!-- Email -->
          <div class="mb-4">
            <label class="block mb-2">Email</label>
            <input 
              v-model="form.email"
              type="email"
              class="w-full p-2 border rounded"
              :class="{'border-red-500': errors.email}"
              required
              @blur="validateEmail"
            >
            <div v-if="errors.email" class="text-red-500 text-sm mt-1">
              {{ errors.email }}
            </div>
          </div>
        </div>
      </div>

      <!-- Endereço -->
      <div class="w-full lg:w-1/3">
        <div class="border rounded-lg p-6 bg-white shadow-md">
          <h2 class="text-xl font-semibold mb-4">Endereço</h2>
          
          <!-- CEP -->
          <div class="mb-4">
            <label class="block mb-2">CEP *</label>
            <input 
              v-model="form.address.cep"
              type="text"
              class="w-full p-2 border rounded"
              :class="{'border-red-500': errors.address.cep}"
              required
              maxlength="9"
              @input="form.address.cep = formatCep($event.target.value)"
              @blur="[validateField('cep', 'address'), searchCep(form.address.cep)]"
              placeholder="00000-000"
            >
            <div v-if="errors.address.cep" class="text-red-500 text-sm mt-1">
              {{ errors.address.cep }}
            </div>
          </div>

          <!-- Rua -->
          <div class="mb-4">
            <label class="block mb-2">Rua *</label>
            <input 
              v-model="form.address.street"
              type="text"
              class="w-full p-2 border rounded"
              :class="{'border-red-500': errors.address.street}"
              required
              @blur="validateField('street', 'address')"
            >
            <div v-if="errors.address.street" class="text-red-500 text-sm mt-1">
              {{ errors.address.street }}
            </div>
          </div>

          <!-- Número e Complemento -->
          <div class="flex gap-4 mb-4">
            <div class="flex-1">
              <label class="block mb-2">Número *</label>
              <input 
                v-model="form.address.number"
                type="number"
                class="w-full p-2 border rounded"
                :class="{'border-red-500': errors.address.number}"
                required
                @blur="validateField('number', 'address')"
              >
              <div v-if="errors.address.number" class="text-red-500 text-sm mt-1">
                {{ errors.address.number }}
              </div>
            </div>
            <div class="flex-1">
              <label class="block mb-2">Complemento</label>
              <input 
                v-model="form.address.complement"
                type="text"
                class="w-full p-2 border rounded"
              >
            </div>
          </div>

          <!-- Bairro -->
          <div class="mb-4">
            <label class="block mb-2">Bairro *</label>
            <input 
              v-model="form.address.neighborhood"
              type="text"
              class="w-full p-2 border rounded"
              :class="{'border-red-500': errors.address.neighborhood}"
              required
              @blur="validateField('neighborhood', 'address')"
            >
            <div v-if="errors.address.neighborhood" class="text-red-500 text-sm mt-1">
              {{ errors.address.neighborhood }}
            </div>
          </div>

          <!-- Cidade e Estado -->
          <div class="flex gap-4 mb-4">
            <div class="flex-1">
              <label class="block mb-2">Cidade *</label>
              <input 
                v-model="form.address.city"
                type="text"
                class="w-full p-2 border rounded"
                :class="{'border-red-500': errors.address.city}"
                required
                @blur="validateField('city', 'address')"
              >
              <div v-if="errors.address.city" class="text-red-500 text-sm mt-1">
                {{ errors.address.city }}
              </div>
            </div>
            <div class="w-32">
              <label class="block mb-2">Estado *</label>
              <input 
                v-model="form.address.state"
                type="text"
                class="w-full p-2 border rounded"
                :class="{'border-red-500': errors.address.state}"
                required
                maxlength="2"
                @blur="validateField('state', 'address')"
              >
              <div v-if="errors.address.state" class="text-red-500 text-sm mt-1">
                {{ errors.address.state }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Telefones -->
      <div class="w-full lg:w-1/3">
        <div class="border rounded-lg p-6 bg-white shadow-md">
          <h2 class="text-xl font-semibold mb-4">Telefones * (Máximo 3)</h2>
          
          <!-- Mensagem de erro geral para telefones -->
          <div v-if="errors.phones && typeof errors.phones === 'string'" class="text-red-500 text-sm mb-4">
            {{ errors.phones }}
          </div>

          <div v-for="(phone, index) in form.phones" :key="index" class="mb-4">
            <div class="flex gap-4 mb-2">
              <!-- Número com DDI incluso -->
              <div class="flex-1">
                <label class="block mb-2">Número</label>
                <input 
                  v-model="phone.number"
                  type="text"
                  class="w-full p-2 border rounded"
                  :class="{'border-red-500': errors.phones[index]?.number}"
                  @input="phone.number = formatPhone($event.target.value)"
                  maxlength="20"
                  placeholder="+55 (00) 00000-0000"
                >
                <div v-if="errors.phones[index]?.number" class="text-red-500 text-sm mt-1">
                  {{ errors.phones[index].number }}
                </div>
              </div>

              <!-- Principal -->
              <div class="w-28 flex items-end mb-2">
                <label class="inline-flex items-center">
                  <input 
                    type="radio" 
                    :checked="phone.is_main"
                    @change="setMainPhone(index)"
                    class="form-radio"
                    name="main_phone"
                  >
                  <span class="ml-2">Principal</span>
                </label>
              </div>
            </div>
            
            <!-- Botão remover -->
            <button 
              v-if="form.phones.length > 1"
              type="button" 
              @click="removePhone(index)"
              class="text-red-500 text-sm hover:text-red-700"
            >
              Remover telefone
            </button>
          </div>

          <!-- Botão adicionar (só aparece se tiver menos que 3 telefones) -->
          <button 
            v-if="form.phones.length < 3"
            type="button"
            @click="addPhone"
            class="text-blue-500 hover:text-blue-700 text-sm"
          >
            + Adicionar telefone
          </button>
        </div>
      </div>
    </div>

    <!-- Botões de Submit e Cancelar -->
    <div class="mt-4 flex justify-end gap-4">
      <button 
        type="button"
        class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-100"
        @click="navigateTo('/')"
      >
        Cancelar
      </button>
      <button 
        type="submit"
        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
        @click.prevent="submitForm"
      >
        Salvar
      </button>
    </div>
  </div>
</template>
