import OneToManyCollectionField from './components/OneToManyCollectionField.vue'
import ManyToAnyCollectionField from './components/ManyToAnyCollectionField.vue'

Nova.booting((app, store) => {
    app.component('form-one-to-many-collection-field', OneToManyCollectionField)
    app.component('form-many-to-any-collection-field', ManyToAnyCollectionField)
})
