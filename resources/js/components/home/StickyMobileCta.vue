<template>
    <Teleport to="body">
        <div v-if="visible"
            class="fixed bottom-0 inset-x-0 z-50 p-3 bg-white/95 backdrop-blur-md border-t border-slate-200 shadow-[0_-4px_16px_rgba(0,0,0,0.08)] lg:hidden safe-area-bottom">
            <router-link to="/motorapp"
                class="flex items-center justify-center gap-2 w-full bg-primary hover:bg-primary-dark text-white py-3.5 rounded-xl typ-t3 font-bold transition-all active:scale-[0.97] shadow-lg shadow-primary/20">
                اشترِ الآن
            </router-link>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const visible = ref( false );
let observer = null;

onMounted( () => {
    const hero = document.querySelector( '[data-section="hero"]' );
    if ( !hero ) { visible.value = true; return; }

    observer = new IntersectionObserver(
        ( [ entry ] ) => { visible.value = !entry.isIntersecting; },
        { threshold: 0 }
    );
    observer.observe( hero );
} );

onUnmounted( () => {
    observer?.disconnect();
} );
</script>

<style scoped>
.safe-area-bottom {
    padding-bottom: max(0.75rem, env(safe-area-inset-bottom));
}
</style>
