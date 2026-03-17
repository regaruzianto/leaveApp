<template>
  <div v-if="errors.length" class="alert-error">
    <p v-for="(msg, i) in errors" :key="i" class="alert-msg">{{ msg }}</p>
  </div>
</template>

<script setup lang="ts">
defineProps<{ errors: string[] }>()
</script>

<style scoped>
.alert-error {
  background: #fee2e2;
  border: 1px solid #fca5a5;
  border-radius: 8px;
  padding: 12px 16px;
  margin-bottom: 16px;
}
.alert-msg {
  color: #991b1b;
  font-size: 14px;
  line-height: 1.5;
}
.alert-msg + .alert-msg {
  margin-top: 4px;
}
</style>
