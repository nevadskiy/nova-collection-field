/**
 * @todo add missing methods for decorator: https://developer.mozilla.org/en-US/docs/Web/API/FormData
 */
export class PathFormData {
    static decorate(formData) {
        if (formData instanceof PathFormData) {
            return formData
        }

        return new PathFormData(formData)
    }

    constructor(formData) {
        this.formData = formData
        this.path = ''
    }

    withAppendingAttribute(attribute, callback) {
        const original = this.path

        this.path = this.concat(attribute)

        callback()

        this.path = original
    }

    concat(attribute) {
        return this.path ? `${this.path}.${attribute}` : attribute
    }

    append(attribute, value) {
        this.formData.append(this.normalizeAttribute(attribute), value)
    }

    normalizeAttribute(attribute) {
        const path = this.concat(attribute)

        const segments = path.split('.');

        let result = segments[0];

        for (let i = 1; i < segments.length; i++) {
            result += `[${segments[i]}]`;
        }

        return result;
    }
}
