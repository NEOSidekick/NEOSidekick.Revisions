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
    };
};

type SetLabelProps = {
    action: 'setlabel';
    params: {
        revision: Revision;
        label: string;
    };
};

type FetchProps = GetRevisionsProps | ApplyRevisionProps | SetLabelProps;

export default function fetchFromBackend(props: FetchProps): Promise<{ revisions: Revision[] }> {
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
        .then((response) => {
            if (!response) {
                return;
            }
            if (response.status === 404) {
                throw new Error(response.message);
            }
            return response.json();
        });
}
