<template>
    <Modal
        show
        size="xl"
        @close-via-escape="() => $emit('close')"
    >
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="border-b border-gray-100 dark:border-gray-700 py-4 px-8">
                <input
                    ref="searchInput"
                    type="search"
                    v-model="search"
                    :placeholder="__(`Search :resource`, { resource: label })"
                    class="w-full form-control form-input-bordered form-input pr-6"
                >
            </div>

            <LoadingView :loading="fetching">
                <ScrollWrap v-if="resources.length > 0" :height="500">
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        <li v-for="resource in resources" :key="resource.id">
                            <button
                                type="button"
                                class="w-full flex items-center text-left py-4 px-8 hover:bg-gray-50 dark:hover:bg-gray-700"
                                :disabled="resource.attached"
                                @click="() => $emit('select', resource)"
                            >
                                <img
                                    v-if="resource.avatar"
                                    :src="resource.avatar"
                                    class="mr-3 inline-flex w-8 h-8 rounded-full flex-shrink-0"
                                />

                                <span class="flex-1" style="min-width: 0">
                                    <span class="flex items-center text-sm font-semibold leading-5">
                                        <span class="truncate mr-3">{{ resource.title }}</span>

                                        <CircleBadge v-if="resource.attached" class="ml-auto">
                                            {{ __('Attached') }}
                                        </CircleBadge>
                                    </span>

                                    <span v-if="resource.subtitle" class="mt-1 block text-xs font-semibold leading-5 text-gray-500 truncate">
                                        {{ resource.subtitle }}
                                    </span>
                                </span>
                            </button>
                        </li>
                    </ul>
                </ScrollWrap>

                <IndexEmptyDialog
                    v-else
                    :resource-name="type"
                    :singular-name="singularLabel"
                />
            </LoadingView>
        </div>
    </Modal>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import debounce from 'lodash/debounce'

// @todo restyle search input
// @todo use keyboard navigation

const props = defineProps({
    size: {
        type: String,
        default: '3xl'
    },

    resourceName: {
        type: String,
        required: true
    },

    attribute: {
        type: String,
        required: true
    },

    type: {
        type: String,
        required: true
    },

    label: {
        type: String,
        required: true
    },

    singularLabel: {
        type: String,
        required: true
    },

    attachedItems: {
        type: Array,
        default: () => []
    },

    debounce: {
        type: Number,
        default: 300
    }
})

defineEmits([
    'select',
    'close',
])

const fetching = ref(false)
const searchInput = ref()
const search = ref('')
const resources = ref([])

function fetchResources() {
    fetching.value = true

    return Nova.request()
        .get(`/nova-vendor/collection-field/${props.resourceName}/${props.attribute}/${props.type}`, {
            params: {
                search: search.value,
            },
        })
        .then(function (response) {
            const attachedResourceMap = props.attachedItems.reduce((map, resource) => map.set(resource.id, resource), new Map());

            resources.value = response.data.data.map(item => ({
                ...item,
                attached: attachedResourceMap.has(item.id)
            }))
        })
        .finally(function () {
            fetching.value = false
        })
}

fetchResources()

const debouncedFetchResources = debounce(fetchResources, props.debounce)

watch(search, function () {
    debouncedFetchResources()
})

onMounted(function () {
    searchInput.value.focus()
})
</script>
