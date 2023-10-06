<template>
    <DefaultField
        :field="field"
        :errors="errors"
        :show-help-text="showHelpText"
        :full-width-content="fullWidthContent"
    >
        <template #field>
            <template v-if="collection.length > 0">
                <CollectionItem
                    v-for="(item, index) in collection"
                    :key="item.uid"
                    ref="itemComponents"
                    :field="field"
                    :fields="item.fields"
                    :errors="errors"
                    :resource-id="resourceId"
                    :resource-name="resourceName"
                    :attribute="`${field.attribute}[${index}]`"
                    :index="index"
                    :id="item.id"
                    :type="item.type"
                    :mode="item.mode"
                    :title="item.singularLabel"
                    :collapsable="field.collapsable"
                    :collapsed-by-default="field.collapsedByDefault"
                    :sortable="!!field.sortBy"
                    @move-up="moveUpItem(index)"
                    @move-down="moveDownItem(index)"
                    @remove="removeItem(index)"
                />
            </template>

            <p v-else class="text-center">
                {{ __('Collection is empty') }}
            </p>

            <div class="flex justify-center space-x-4">
                <Dropdown>
                    <DropdownTrigger class="link-default inline-flex items-center cursor-pointer px-3 space-x-1">
                        <Icon type="plus-circle" />
                        <span class="font-bold">{{ __('Create item') }}</span>
                    </DropdownTrigger>

                    <template #menu>
                        <DropdownMenu width="auto" class="py-1">
                            <DropdownMenuItem
                                v-for="resource in field.resources"
                                :key="resource.name"
                                as="button"
                                class="space-x-2"
                                @click="() => addResourceItem(resource)"
                            >
                                <!--@todo icons-->
                                <!--<span><Icon :name="resource.icon" /></span>-->
                                <span>{{ resource.singularLabel }}</span>
                            </DropdownMenuItem>
                        </DropdownMenu>
                    </template>
                </Dropdown>

                <Dropdown v-if="field.attachable">
                    <DropdownTrigger class="link-default inline-flex items-center cursor-pointer px-3 space-x-1">
                        <Icon type="paper-clip" />
                        <span class="font-bold">{{ __('Attach item') }}</span>
                    </DropdownTrigger>

                    <template #menu>
                        <DropdownMenu width="auto" class="py-1">
                            <DropdownMenuItem
                                v-for="resource in field.resources"
                                :key="resource.name"
                                as="button"
                                class="space-x-2"
                                @click="() => openAttachingResourceModal(resource)"
                            >
                                <!--@todo icons-->
                                <!--<span><Icon :name="resource.icon" /></span>-->
                                <span>{{ resource.singularLabel }}</span>
                            </DropdownMenuItem>
                        </DropdownMenu>
                    </template>
                </Dropdown>
            </div>

            <SelectAttachableResourceModal
                v-if="attachableResource"
                :resource-name="resourceName"
                :attribute="field.attribute"
                :type="attachableResource.type"
                :label="attachableResource.label"
                :singular-label="attachableResource.singularLabel"
                :attached-items="attachedItems"
                @select="(resource) => attachResourceItem(resource)"
                @close="() => attachableResource = null"
            />
        </template>
    </DefaultField>
</template>

<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova'
import clone from 'lodash/clone'
import uniqueId from 'lodash/uniqueId'
import CollectionItem from "./CollectionItem.vue";
import SelectAttachableResourceModal from "./SelectAttachableResourceModal.vue";

export default {
    mixins: [
        FormField,
        HandlesValidationErrors
    ],

    components: {
        SelectAttachableResourceModal,
        CollectionItem
    },

    props: [
        'resourceName',
        'resourceId',
        'field'
    ],

    data () {
        return {
            attachableResource: null,
        }
    },

    computed: {
        collection() {
            return this.value
        },

        attachedItems() {
            if (!this.attachableResource) {
                return []
            }

            return this.collection.filter(item => item.type === this.attachableResource.type && item.id)
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

            for (const itemComponent of this.$refs.itemComponents) {
                itemComponent.fill(formData)
            }
        },

        addResourceItem(resource) {
            this.collection.push({
                uid: this.generateUniqueId(),
                id: null,
                type: resource.type,
                mode: 'create',
                singularLabel: resource.singularLabel,
                fields: clone(resource.fields),
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

        openAttachingResourceModal(resource) {
            this.attachableResource = resource
        },

        closeAttachingResourceModal() {
            this.attachableResource = null
        },

        attachResourceItem(resource) {
            this.collection.push({
                uid: this.generateUniqueId(),
                id: resource.id,
                type: resource.type,
                mode: 'attach',
                singularLabel: resource.singularLabel,
                fields: clone(resource.fields),
            })

            this.closeAttachingResourceModal()
        },

        generateUniqueId() {
            return uniqueId('uid_')
        },
    },
}
</script>
