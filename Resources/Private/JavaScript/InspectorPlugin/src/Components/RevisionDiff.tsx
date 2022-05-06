import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Icon, Button } from '@neos-project/react-ui-components';

import Revision from '../Interfaces/Revision';
import Node from '../Interfaces/Node';
import fetchFromBackend from '../Api/fetch';
import ContentChangeDiff from './ContentChangeDiff';
import ErrorBoundary from './ErrorBoundary';
import { formatRevisionDate } from '../Helpers/format';

type RevisionDiffProps = {
    documentNode: Node;
    revision: Revision;
    translate: (id: string, fallback?: string, params?: Record<string, unknown> | string[]) => string;
    onClose: () => void;
    applyRevision: (revision: Revision, force?: boolean) => void;
    contentDimensions: ContentDimensions;
};

const RevisionDiff: React.FC<RevisionDiffProps> = ({
    documentNode,
    revision,
    translate,
    applyRevision,
    onClose,
    contentDimensions,
}) => {
    const [isLoading, setIsLoading] = useState(true);
    const [changes, setChanges] = useState<ChangeList>({});
    const [message, setMessage] = useState('');

    const version = useMemo(
        () =>
            revision ? revision.label ||
            translate('revision.label', `By ${revision.creator}`, {
                creator: revision.creator,
            }) : '',
        [revision]
    );

    const fetchChanges = useCallback(() => {
        setIsLoading(true);
        fetchFromBackend<{ diff: ChangeList }>(
            { action: 'getDiff', params: { node: documentNode, revision } },
            setIsLoading
        )
            .then(({ diff }) => setChanges(diff))
            .catch((error) => {
                setMessage(translate('error.failedFetchingChanges'));
                console.error(error.message);
            });
    }, [revision]);

    useEffect(() => {
        fetchChanges();
    }, [revision]);

    return (
        <div style={{ padding: '1rem' }}>
            <h1 style={{ marginBottom: '2rem', fontSize: '1.5em' }}>
                {translate('diff.header', 'The revision contains the following changes', { version, date: formatRevisionDate(revision) })}
            </h1>
            {isLoading ? (
                <div>
                    <Icon icon="spinner" spin color="primaryBlue" /> Loading …
                </div>
            ) : changes ? (
                Object.keys(changes).map((nodeIdentifier) => Object.keys(changes[nodeIdentifier]).map((dimensionHash) => (
                    <div key={nodeIdentifier} style={{ marginBottom: '1rem' }}>
                        <ErrorBoundary
                            text={`Diff for node ${changes[nodeIdentifier][dimensionHash].node?.label || nodeIdentifier} could not be rendered. Please check the logs.`}
                        >
                            <ContentChangeDiff
                                nodeChanges={changes[nodeIdentifier][dimensionHash]}
                                translate={translate}
                                contentDimensions={contentDimensions}
                            />
                        </ErrorBoundary>
                    </div>
                ))
            )) : (
                <p>{message}</p>
            )}
            <div style={{ marginTop: '2rem', display: 'flex', justifyContent: 'space-between' }}>
                <Button style="warn" onClick={onClose}>
                    {translate('action.close')}
                </Button>
                <Button style="success" onClick={() => applyRevision(revision)} disabled={isLoading}>
                    <Icon icon="check" /> {translate('action.apply')}
                </Button>
            </div>
        </div>
    );
};

export default React.memo(RevisionDiff);
