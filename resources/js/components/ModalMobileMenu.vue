<template>
    <modal :show="show" ref="modal">
        <template>
            <div
                class="flex flex-col justify-between h-auto min-h-screen pt-12 border-t-4 mt-[76px] border-violet bg-blue-black"
            >
                <nav class="mt-2">
                    <div class="border-b border-silver">
                        <slot name="navigation-links"></slot>
                    </div>

                    <div class="py-4">
                        <slot name="subnavigation-links"></slot>
                    </div>
                </nav>

                <div
                    class="flex items-center justify-between px-4 py-10 mt-4 border-t border-silver"
                >
                    <slot name="navigation-buttons"></slot>
                </div>
            </div>
        </template>
    </modal>
</template>

<script>
export default {
    props: {
        show: {}
    },

    methods: {
        closeModal() {
            if (window.innerWidth >= 992) {
                this.$refs.modal.close();
            }
        }
    },

    watch: {
        show(value) {
            if (value) {
                window.addEventListener("resize", this.closeModal);
            } else {
                window.removeEventListener("resize", this.closeModal);
            }
        }
    }
};
</script>
