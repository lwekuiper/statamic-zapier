import Index from './pages/Index.vue';
import Empty from './pages/Empty.vue';
import Edit from './pages/Edit.vue';
import FormFieldsFieldtype from './components/fieldtypes/ZapierFormFieldsFieldtype.vue';
import { HANDLE } from './integration.js';

export function registerIntegration({ fieldtypes = {} } = {}) {
    Statamic.booting(() => {
        Statamic.$inertia.register(`${HANDLE}::Index`, Index);
        Statamic.$inertia.register(`${HANDLE}::Empty`, Empty);
        Statamic.$inertia.register(`${HANDLE}::Edit`, Edit);

        Statamic.$components.register(`${HANDLE}_form_fields-fieldtype`, FormFieldsFieldtype);

        Object.entries(fieldtypes).forEach(([component, definition]) => {
            Statamic.$components.register(`${component}-fieldtype`, definition);
        });
    });
}
