export default interface Revision {
    creationDateTime: string;
    creator: string;
    label: string;
    nodeIdentifier: string;
    identifier: string;
    isEmpty: boolean;
    isMoved: boolean;
    appliedRevision: {
        identifier: string;
        label: string;
        creationDateTime: string;
    } | null;
}
