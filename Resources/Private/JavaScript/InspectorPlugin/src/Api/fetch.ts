// @ts-ignore
import { fetchWithErrorHandling } from '@neos-project/neos-ui-backend-connector';

import Node from '../Interfaces/Node';
import Revision from '../Interfaces/Revision';

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
        force?: boolean;
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

type FetchProps = GetRevisionsProps | ApplyRevisionProps | DeleteRevisionProps | SetLabelProps;

class ApplyError extends Error {
    private readonly _status: number;
    private readonly _conflicts: string[];

    constructor(message: string, status: number, conflicts?: string[]) {
        super(message);
        this.name = 'ApplyError';
        this._status = status;
        this._conflicts = conflicts;
    }

    get status(): number {
        return this._status;
    }

    get conflicts(): string[] {
        return this._conflicts;
    }
}

export default function fetchFromBackend(
    props: FetchProps,
    setLoadingState: (state) => void
): Promise<{ revisions: Revision[] }> {
    setLoadingState(true);

    // Cannot use URL object here due to missing Safari support
    let url = `/neos/codeq/revisions/${props.action}?`;

    if (props.params['node']) {
        url += `&node=${encodeURIComponent(props.params['node'].contextPath)}`;
    }
    if (props.params['revision']) {
        url += `&revision=${encodeURIComponent(props.params['revision'].identifier)}`;
    }
    if (props.params['label']) {
        url += `&label=${encodeURIComponent(props.params['label'])}`;
    }
    if (props.params['force']) {
        url += `&force=${encodeURIComponent(props.params['force'])}`;
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
        }))
        .then(async (response) => {
            setLoadingState(false);
            if (!response) {
                return;
            }
            if (response.status >= 400 && response.status < 600) {
                const { message } = response;
                if (props.action === 'apply') {
                    let conflicts: string[] = [];
                    try {
                        conflicts = await response.json();
                    } catch (e) {
                        // noop
                    }
                    throw new ApplyError(message, response.status, conflicts);
                }
                throw new Error(message);
            }
            return response.json();
        });
}
