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
                    :attribute="`${field.attribute}[${index}]`"
                    :index="index"
                    :id="item.id"
                    :mode="item.mode"
                    :title="item.singularLabel"
                    :collapsable="field.collapsable"
                    :collapsed-by-default="field.collapsedByDefault"
                    :sortable="!!field.sortBy"
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
                    <span class="font-bold">{{ __('Create item') }}</span>
                </BasicButton>
            </div>
        </template>
    </DefaultField>
</template>

<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova'
import clone from 'lodash/clone'
import uniqueId from 'lodash/uniqueId'
import CollectionItem from "./CollectionItem.vue";

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
                ...item,
                mode: 'update',
            }))
        },

        fill(formData) {
            if (this.$refs.itemComponents === undefined) {
                return;
            }

            for (const itemComponent of this.$refs.itemComponents) {
                itemComponent.fill(formData)
            }
        },

        createItem() {
            this.collection.push({
                uid: this.generateUniqueId(),
                id: null,
                mode: 'create',
                singularLabel: this.field.resource.singularLabel,
                fields: clone(this.field.resource.fields),
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
