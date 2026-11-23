<template>
  <div class="feder-brand-symbol" :style="{ width: size + 'px', height: size + 'px' }">
    <img
      src="/favicon.png"
      alt="Feder"
      class="feder-logo-image"
      :style="{ width: size + 'px', height: size + 'px' }"
      loading="eager"
      @error="handleImgError"
    />
  </div>
</template>

<script setup>
defineProps({
  size: {
    type: [Number, String],
    default: 34,
  },
});

const handleImgError = (event) => {
  const target = event.target;
  if (target && target.src && !target.src.includes('assets/img')) {
    target.src = '/assets/img/favicon.png';
  }
};
</script>

<style scoped>
.feder-brand-symbol {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  line-height: 1;
}

.feder-logo-image {
  display: block;
  object-fit: contain;
  border-radius: 8px;
  flex-shrink: 0;
}
</style>
