export type ResolutionChoice = '__skip' | '__moveBack' | '__remove';

export type Resolutions = Record<string, Partial<Record<ResolutionChoice, boolean>>>;

export interface ResolutionRow {
    identifier: string;
    label: string;
    nodeType: {
        name: string;
        label: string;
    };
    path: string;
    dimensions: Record<string, string[]>[];
    problems: {
        id: 'nodeTypeMissing' | 'nodeTypeNotAllowed' | 'parentMissing' | 'movedAway' | 'movedHere';
        message: string;
    }[];
    document: {
        identifier: string;
        label: string;
    } | null;
    choices: ResolutionChoice[];
    resolution: ResolutionChoice | null;
}
