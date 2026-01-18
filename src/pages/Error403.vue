<template>
  <div class="fullscreen bg-red text-white text-center q-pa-md flex flex-center">
    <div>
      <div style="font-size: 30vh">
        403
      </div>

      <div class="text-h2" style="opacity:.4">
        Access Denied
      </div>

      <div class="text-h5 q-mt-md" style="opacity:.6">
        You do not have permission to view this page.
      </div>

      <q-btn
        class="q-mt-xl"
        color="white"
        text-color="red"
        unelevated
        to="/"
        label="Go Home"
        no-caps
      />
    </div>
  </div>
</template>

<script setup>
</script>
