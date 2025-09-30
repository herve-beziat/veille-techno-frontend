<script setup lang="ts">
import Sidebar from './components/Sidebar.vue'
</script>

<template>
  <div class="app">
    <Sidebar />
    <main class="content">
      <router-view />
    </main>
  </div>
</template>

<style>
.app {
  display: flex;
}

.content {
  margin-left: 240px; /* espace réservé pour la sidebar */
  padding: 2rem;
  flex: 1;
}
</style>
