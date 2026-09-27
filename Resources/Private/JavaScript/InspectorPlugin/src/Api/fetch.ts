// @ts-ignore
import { fetchWithErrorHandling } from '@neos-project/neos-ui-backend-connector';

import Node from '../Interfaces/Node';
import Revision from '../Interfaces/Revision';
import { ResolutionRow, Resolutions } from '../Interfaces/Resolution';

type GetRevisionsProps = {
    action: 'get';
    params: {
        node: Node;
    };
};

type ApplyRevisionProps = {
    action: 'apply';
    params: {
        node: Node;
        revision: Revision;
        resolutions: Resolutions;
    };
};

type DeleteRevisionProps = {
    action: 'delete';
    params: {
        revision: Revision;
    };
};

type SetLabelProps = {
    action: 'setlabel';
    params: {
        revision: Revision;
        label: string;
    };
};

type GetDiffProps = {
    action: 'getDiff';
    params: {
        node: Node;
        revision: Revision;
    };
};

type FetchProps = GetRevisionsProps | ApplyRevisionProps | DeleteRevisionProps | SetLabelProps | GetDiffProps;

class ApplyError extends Error {
    private readonly _status: number;
    private readonly _rows: ResolutionRow[];
    private readonly _errors: string[];

    constructor(message: string, status: number, rows: ResolutionRow[], errors: string[]) {
        super(message);
        this.name = 'ApplyError';
        this._status = status;
        this._rows = rows;
        this._errors = errors;
    }

    get status(): number {
        return this._status;
    }

    get rows(): ResolutionRow[] {
        return this._rows;
    }

    get errors(): string[] {
        return this._errors;
    }
}

export default function fetchFromBackend<T = Record<string, unknown>>(
    props: FetchProps,
    setLoadingState: (state) => void
): Promise<T> {
    setLoadingState(true);

    // Cannot use URL object here due to missing Safari support
    let url = `/neos/neosidekick/revisions/${props.action}?`;

    if (props.params['node']) {
        url += `&node=${encodeURIComponent(props.params['node'].contextPath)}`;
    }
    if (props.params['revision']) {
        url += `&revision=${encodeURIComponent(props.params['revision'].identifier)}`;
    }
    if (props.params['label']) {
        url += `&label=${encodeURIComponent(props.params['label'])}`;
    }

    return fetchWithErrorHandling
        .withCsrfToken((csrfToken) => ({
            url,
            method: props.action === 'get' ? 'GET' : 'POST',
            credentials: 'include',
            headers: {
                'X-Flow-Csrftoken': csrfToken,
                'Content-Type': 'application/json',
            },
            // Flow maps a JSON body to the action arguments
            body: props.action === 'apply' ? JSON.stringify({ resolutions: props.params.resolutions }) : undefined,
        }))
        .then(async (response) => {
            if (!response) {
                return;
            }
            if (response.status >= 400 && response.status < 600) {
                const { message } = response;
                if (props.action === 'apply') {
                    let body = { rows: [], errors: [] };
                    try {
                        body = { ...body, ...(await response.json()) };
                    } catch (e) {
                        // A server error has no JSON body
                    }
                    throw new ApplyError(message, response.status, body.rows, body.errors);
                }
                throw new Error(message);
            }
            return response.json();
        })
        .finally(() => {
            setLoadingState(false);
        });
}
