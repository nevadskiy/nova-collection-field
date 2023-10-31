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
                #{{ index + 1 }} {{ title }}
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
                v-for="(field, index) in fields"
                :key="index"
                :is="`form-${field.component}`"
                :field="field"
                :errors="errors"
                :resource-id="resourceId"
                :resource-name="resourceName"
            />
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps([
    'fields',
    'errors',
    'resourceName',
    'resourceId',
    'path',
    'index',
    'id',
    'type',
    'mode',
    'title',
    'collapsable',
    'collapsedByDefault',
    'sortable',
])

const emits = defineEmits([
    'move-up',
    'move-down',
    'remove'
])

const collapsed = ref(props.collapsedByDefault)

const toggleCollapse = function () {
    collapsed.value = !collapsed.value
}

const fill = function (nestedFormData) {
    nestedFormData.withConcat(props.index, () => {
        if (props.id) {
            nestedFormData.append('id', props.id)
        }

        if (props.type) {
            nestedFormData.append('type', props.type)
        }

        nestedFormData.append('mode', props.mode)

        nestedFormData.withConcat('attributes', () => {
            for (const field of (props.fields ?? [])) {
                field.fill(nestedFormData)
            }
        })
    })
}

defineExpose({
    fill
})
</script>
