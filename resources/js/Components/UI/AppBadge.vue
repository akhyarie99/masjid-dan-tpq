<template>
  <span :class="classes[variant]">
    <slot />
  </span>
</template>

<script setup>
defineProps({
  variant: { type: String, default: 'gray' }, // green | red | yellow | blue | gray
})

const classes = {
  green: 'badge-green',
  red: 'badge-red',
  yellow: 'badge-yellow',
  blue: 'badge-blue',
  gray: 'badge-gray',
}
</script>
