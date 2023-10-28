<template>
    <DefaultField
        :field="field"
        :errors="errors"
        :show-help-text="showHelpText"
        :full-width-content="fullWidthContent"
    >
        <template #field>
            <div v-if="collection.length > 0" class="space-y-4">
                <CollectionItem
                    v-for="(item, index) in collection"
                    :key="item.uid"
                    ref="itemComponents"
                    :field="field"
                    :fields="item.fields"
                    :errors="errors"
                    :resource-id="item.id"
                    :resource-name="field.resource.type"
                    :index="index"
                    :id="item.id"
                    :mode="item.mode"
                    :title="item.singularLabel"
                    :collapsable="field.collapsable"
                    :collapsed-by-default="field.collapsedByDefault"
                    :sortable="field.sortable"
                    @move-up="moveUpItem(index)"
                    @move-down="moveDownItem(index)"
                    @remove="removeItem(index)"
                />
            </div>

            <p v-else class="text-center">
                {{ __('Collection is empty') }}
            </p>

            <div class="text-center">
                <BasicButton
                    type="button"
                    class="link-default inline-flex items-center px-3 space-x-1"
                    @click="() => createItem()"
                >
                    <Icon type="plus-circle" />
                    <span class="font-bold">{{ __('Create :resource', { resource: field.resource.singularLabel }) }}</span>
                </BasicButton>
            </div>
        </template>
    </DefaultField>
</template>

<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova'
import cloneDeep from 'lodash/cloneDeep'
import uniqueId from 'lodash/uniqueId'
import CollectionItem from "./CollectionItem.vue"
import { NestedFormData } from '../nested-form-data'

export default {
    mixins: [
        FormField,
        HandlesValidationErrors
    ],

    components: {
        CollectionItem
    },

    props: [
        'resourceName',
        'resourceId',
        'field'
    ],

    computed: {
        collection() {
            return this.value
        }
    },

    methods: {
        setInitialValue() {
            this.value = (this.field.value ?? []).map(item => ({
                uid: this.generateUniqueId(),
                mode: 'update',
                ...item,
            }))
        },

        fill(formData) {
            if (this.$refs.itemComponents === undefined) {
                return;
            }

            const nestedFormData = NestedFormData.decorate(formData)

            nestedFormData.withConcat(this.field.attribute, () => {
                for (const itemComponent of this.$refs.itemComponents) {
                    itemComponent.fill(nestedFormData)
                }
            })
        },

        createItem() {
            const clone = cloneDeep(this.field.resource)

            this.collection.push({
                uid: this.generateUniqueId(),
                id: null,
                mode: 'create',
                singularLabel: clone.singularLabel,
                fields: clone.fields,
            })
        },

        removeItem(index) {
            this.collection.splice(index, 1)
        },

        moveUpItem(index) {
            const newIndex = (index - 1 + this.collection.length) % this.collection.length

            const [item] = this.collection.splice(index, 1)

            this.collection.splice(newIndex, 0, item)
        },

        moveDownItem(index) {
            const newIndex = (index + 1) % this.collection.length

            const [item] = this.collection.splice(index, 1)

            this.collection.splice(newIndex, 0, item)
        },

        generateUniqueId() {
            return uniqueId('uid_')
        },
    },
}
</script>
