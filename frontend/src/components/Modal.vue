<template>
  <Teleport to="body">
    <div v-if="modelValue" class="modal-overlay" @click.self="$emit('update:modelValue', false)">
      <div class="modal-box" :style="{ maxWidth: width }">
        <div class="modal-header">
          <div>
            <h2 class="modal-title">{{ title }}</h2>
            <p v-if="subtitle" class="modal-subtitle">{{ subtitle }}</p>
          </div>
          <button class="modal-close" @click="$emit('update:modelValue', false)">✕</button>
        </div>
        <div class="modal-body">
          <slot />
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
defineProps<{
  modelValue: boolean
  title: string
  subtitle?: string
  width?: string
}>()
defineEmits<{ (e: 'update:modelValue', v: boolean): void }>()
</script>

<style scoped>
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.45);
  display: flex; align-items: center; justify-content: center;
  z-index: 1000;
  padding: 16px;
}
.modal-box {
  background: #fff;
  border-radius: 12px;
  width: 100%;
  max-width: 520px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}
.modal-header {
  display: flex; justify-content: space-between; align-items: flex-start;
  padding: 24px 24px 16px;
  border-bottom: 1px solid #f3f4f6;
}
.modal-title { font-size: 18px; font-weight: 700; color: #111827; }
.modal-subtitle { font-size: 13px; color: #6b7280; margin-top: 2px; }
.modal-close {
  background: none; border: none; cursor: pointer;
  color: #9ca3af; font-size: 18px; line-height: 1;
  padding: 4px 8px;
  border-radius: 4px;
  transition: background 0.15s;
}
.modal-close:hover { background: #f3f4f6; color: #374151; }
.modal-body { padding: 20px 24px 24px; }
</style>
