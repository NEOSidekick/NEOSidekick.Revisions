import Revision from '../Interfaces/Revision';

export function formatRevisionDate(revision: Revision): string {
    return formatChangeDate(revision.creationDateTime);
}

// Labels of revisions created by applying another one are rendered here, in the viewer's language
export function formatRevisionLabel(
    revision: Revision,
    translate: (id: string, fallback?: string, params?: Record<string, unknown> | string[]) => string
): string {
    if (revision.label || !revision.appliedRevision) {
        return revision.label;
    }
    return translate('action.apply.newRevisionLabel', 'Applied revision {revision}', {
        revision: revision.appliedRevision.label || formatChangeDate(revision.appliedRevision.creationDateTime),
    });
}

export function formatChangeDate(datetime: string | number): string {
    return new Date(datetime).toLocaleString(undefined, {
        year: '2-digit',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}
