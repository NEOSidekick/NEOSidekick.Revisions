import Revision from '../Interfaces/Revision';

export function formatRevisionDate(revision: Revision): string {
    return new Date(revision.creationDateTime).toLocaleString(undefined, {
        year: '2-digit',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}
