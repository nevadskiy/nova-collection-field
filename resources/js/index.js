import CollectionField from './components/CollectionField.vue'
import MorphCollectionField from './components/MorphCollectionField.vue'

Nova.booting((app, store) => {
    app.component('form-collection-field', CollectionField)
    app.component('form-morph-collection-field', MorphCollectionField)
})
