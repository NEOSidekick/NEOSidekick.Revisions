import manifest from '@neos-project/neos-ui-extensibility';
import RevisionsView from './RevisionsView';

manifest('CodeQ:Revisions', {}, (globalRegistry) => {
    const viewsRegistry = globalRegistry.get('inspector').get('views');

    viewsRegistry.set('NEOSidekick.Revisions/Inspector/Views/RevisionsView', {
        component: RevisionsView,
    });
});
