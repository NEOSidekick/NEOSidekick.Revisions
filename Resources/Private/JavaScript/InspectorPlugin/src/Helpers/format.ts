import Revision from '../Interfaces/Revision';

export function formatRevisionDate(revision: Revision): string {
    return formatChangeDate(revision.creationDateTime);
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
