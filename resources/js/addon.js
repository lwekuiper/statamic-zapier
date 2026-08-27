import { registerIntegration } from './register.js';
import ZapierSitesFieldtype from './components/fieldtypes/ZapierSitesFieldtype.vue';

registerIntegration({
    fieldtypes: {
        zapier_sites: ZapierSitesFieldtype,
    },
});
