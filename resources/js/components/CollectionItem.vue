<template>
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded divide-y divide-gray-200 dark:divide-gray-700">
        <div class="flex items-center bg-gray-50 dark:bg-gray-700 py-2 px-3 rounded-t">
            <button
                v-if="collapsable"
                type="button"
                class="rounded border border-transparent h-6 w-6 ml-1 inline-flex items-center justify-center focus:outline-none focus:ring focus:ring-primary-200"
                :aria-label="__('Toggle Collapsed')"
                :aria-expanded="collapsed === false ? 'true' : 'false'"
                @click="toggleCollapse"
            >
                <CollapseButton :collapsed="collapsed" />
            </button>

            <h4 class="ml-3 font-bold mr-auto">
                {{ title }}
            </h4>

            <IconButton
                v-if="sortable"
                iconType="arrow-circle-up"
                solid
                small
                class="ml-3"
                @click="() => emits('move-up')"
            />

            <IconButton
                v-if="sortable"
                iconType="arrow-circle-down"
                solid
                small
                class="ml-3"
                @click="() => emits('move-down')"
            />

            <IconButton
                iconType="trash"
                solid
                small
                class="ml-3"
                @click="() => emits('remove')"
            />
        </div>

        <div v-show="!collapsed" class="grid grid-cols-full divide-y divide-gray-100 dark:divide-gray-700">
            <component
                v-for="(field, fieldIndex) in fields"
                :key="fieldIndex"
                :is="`form-${field.component}`"
                :field="field"
                :errors="itemErrors"
                :resource-id="resourceId"
                :resource-name="resourceName"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import pickBy from "lodash/pickBy";
import mapKeys from "lodash/mapKeys";

const props = defineProps({
    id: {
        type: [String, Number],
        default: null
    },

    type: {
        type: [String, Number],
        default: null
    },

    mode: {
        type: String,
        required: true
    },

    attribute: {
        type: String,
        required: true
    },

    index: {
        type: Number,
        required: true
    },

    fields: {
        type: Array,
        required: true
    },

    errors: {
        type: Object,
        default: null
    },

    resourceId: {
        type: [String, Number],
        default: null
    },

    resourceName: {
        type: String,
        required: true
    },

    title: {
        type: String,
        required: true
    },

    collapsable: {
        type: Boolean,
        default: false
    },

    collapsedByDefault: {
        type: Boolean,
        default: false
    },

    sortable: {
        type: Boolean,
        default: false
    },

    skipIfNoChanges: {
        type: Boolean,
        default: false
    }
})

const emits = defineEmits([
    'move-up',
    'move-down',
    'remove'
])

const itemErrors = computed(() => {
    const path = `${props.attribute}.${props.index}.attributes.`

    const errors = {}

    for (const key in props.errors) {
        if (key.startsWith(path)) {
            errors[key.replace(path, '')] = props.errors[key];
        }
    }

    return errors;
})

const collapsed = ref(props.collapsedByDefault)

function toggleCollapse() {
    collapsed.value = !collapsed.value
}

function fillAttributesToFormData(formData) {
    for (const field of (props.fields ?? [])) {
        field.fill(formData)
    }
}

const originalAttributesFormData = new FormData()

onMounted(function () {
    fillAttributesToFormData(originalAttributesFormData)
})

function fill(nestedFormData) {
    nestedFormData.withNesting(props.index, () => {
        if (props.id) {
            nestedFormData.append('id', props.id)
        }

        if (props.type) {
            nestedFormData.append('type', props.type)
        }

        nestedFormData.append('mode', props.mode)

        nestedFormData.withNesting('attributes', () => {
            fillAttributes(nestedFormData)
        })
    })
}

function fillAttributes(formData) {
    if (props.skipIfNoChanges) {
        const currentAttributesFormData = new FormData()

        fillAttributesToFormData(currentAttributesFormData)

        if (! compareFormData(originalAttributesFormData, currentAttributesFormData)) {
            copyFormData(currentAttributesFormData, formData)
        }
    } else {
        fillAttributesToFormData(formData)
    }
}

function compareFormData(source, target) {
    for (let [key, value] of target.entries()) {
        if (source.get(key) !== value) {
            return false
        }
    }

    return true
}

function copyFormData(source, target) {
    for (let [key, value] of source.entries()) {
        target.append(key, value)
    }
}

defineExpose({
    fill
})
</script>
