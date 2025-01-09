<script setup lang="ts">
interface Toast {
  id: number;
  message: string;
  type: 'success' | 'error';
}

const props = defineProps<{
  toasts: Toast[]
}>()

const emit = defineEmits<{
  remove: [id: number]
}>()

const getToastClasses = (type: 'success' | 'error') => {
  return {
    'bg-green-500': type === 'success',
    'bg-red-500': type === 'error',
  }
}
</script>

<template>
  <div class="fixed top-4 right-4 z-50 space-y-2">
    <TransitionGroup name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="[
          'px-4 py-2 rounded-lg text-white shadow-lg min-w-[300px]',
          getToastClasses(toast.type)
        ]"
      >
        <div class="flex items-center justify-between">
          <span>{{ toast.message }}</span>
          <button
            @click="emit('remove', toast.id)"
            class="ml-4 hover:opacity-80"
          >
            ✕
          </button>
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}
</style>
