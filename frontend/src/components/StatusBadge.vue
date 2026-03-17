<template>
  <span :class="['badge', `badge--${status}`]">
    <span class="dot" />
    {{ status }}
  </span>
</template>

<script setup lang="ts">
import type { LeaveStatus } from '@/types'
defineProps<{ status: LeaveStatus }>()
</script>

<style scoped>
.badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  text-transform: lowercase;
}
.dot {
  width: 7px; height: 7px;
  border-radius: 50%;
}
.badge--pending  { background: #fef3c7; color: #92400e; }
.badge--pending .dot  { background: #d97706; }
.badge--approved { background: #d1fae5; color: #065f46; }
.badge--approved .dot { background: #059669; }
.badge--rejected { background: #fee2e2; color: #991b1b; }
.badge--rejected .dot { background: #dc2626; }
.badge--cancelled { background: #e5e7eb; color: #374151; }
.badge--cancelled .dot { background: #6b7280; }
</style>
