import ManyToAnyCollectionField from './components/ManyToAnyCollectionField.vue'

Nova.booting((app, store) => {
    app.component('form-many-to-any-collection-field', ManyToAnyCollectionField)
})
