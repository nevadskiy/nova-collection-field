export class NestedFormData {
    static wrap(formData) {
        if (formData instanceof NestedFormData) {
            return formData
        }

        return new NestedFormData(formData)
    }

    constructor(formData) {
        this.formData = formData
        this.path = ''
    }

    withNesting(attribute, callback) {
        const original = this.path

        this.path = this.#nest(attribute)

        callback()

        this.path = original
    }

    append(attribute, value) {
        const path = this.#nest(attribute)

        this.formData.append(this.#formatPath(path), value)
    }

    #nest(attribute) {
        attribute = String(attribute).replace(/\[(\w+)\]/g, '.$1')

        return this.path ?
            `${this.path}.${attribute}`
            : attribute
    }

    #formatPath(path) {
        const segments = path.split('.');

        let result = segments[0];

        for (let i = 1; i < segments.length; i++) {
            result += `[${segments[i]}]`;
        }

        return result;
    }
}
