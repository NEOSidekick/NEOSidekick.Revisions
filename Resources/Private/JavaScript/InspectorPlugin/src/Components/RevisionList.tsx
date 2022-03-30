import React, { useCallback, useEffect, useState } from 'react';
import { IconButton, Icon } from '@neos-project/react-ui-components';

import Node from '../Interfaces/Node';
import Revision from '../Interfaces/Revision';
import fetchFromBackend from '../Api/fetch';
import { formatRevisionDate } from '../Helpers/format';
import I18nRegistry from '../Interfaces/I18nRegistry';
import RevisionDetails from './RevisionDetails';

interface Props {
    documentNode: Node;
    addFlashMessage: (title: string, message: string, severity?: string, timeout?: number) => void;
    reloadDocument: () => void;
    i18nRegistry: I18nRegistry;
}

const RevisionList: React.FC<Props> = ({ documentNode, addFlashMessage, reloadDocument, i18nRegistry }) => {
    const [revisions, setRevisions] = useState<Revision[]>([]);
    const [message, setMessage] = useState('');
    const [selectedRevision, setSelectedRevision] = useState<Revision>(null);
    const [isLoading, setIsLoading] = useState(false);

    const translate = useCallback(
        (
            id: string,
            fallback = '',
            params: Record<string, unknown> | string[] = [],
            packageKey = 'CodeQ.Revisions',
            sourceName = 'Main'
        ): string => {
            return i18nRegistry.translate(id, fallback, params, packageKey, sourceName);
        },
        []
    );

    const fetchRevisions = useCallback(() => {
        setIsLoading(true);
        fetchFromBackend({ action: 'get', params: { node: documentNode } }, setIsLoading)
            .then(({ revisions }: { revisions: Revision[] }) => setRevisions(revisions))
            .catch((error) => {
                setMessage(translate('error.failedFetchingExpressions'));
                console.error(error.message);
            });
    }, [documentNode]);

    const resolveConflicts = useCallback((revision: Revision, conflicts: string[]) => {
        if (
            confirm(
                translate(
                    'error.verifyResolveConflicts',
                    'Some conflicts were detected. Do you still want to apply the revision?{conflicts}',
                    { conflicts: '\n\n' + conflicts.join('\n\n') }
                )
            )
        ) {
            applyRevision(revision, true);
        }
    }, []);

    const applyRevision = useCallback((revision: Revision, force = false) => {
        if (
            force ||
            confirm(
                translate('confirm.applyRevision', 'Do you want to apply revision {revisionDate} by {creator}?', {
                    revisionDate: formatRevisionDate(revision),
                    creator: revision.creator,
                })
            )
        ) {
            fetchFromBackend(
                {
                    action: 'apply',
                    params: { node: documentNode, revision, force },
                },
                setIsLoading
            )
                .then(() => {
                    addFlashMessage(
                        translate('success.revisionApplied'),
                        `Revision "${revision.label || formatRevisionDate(revision)}" by "${
                            revision.creator
                        }" applied.`,
                        'success'
                    );
                    reloadDocument();
                    setMessage('');
                })
                .catch((error) => {
                    const { message, status, conflicts } = error;
                    if (status === 409) {
                        resolveConflicts(revision, conflicts);
                    } else {
                        setMessage(translate('error.failedApplyingRevision'));
                        console.error(message);
                    }
                });
        }
    }, []);

    const deleteRevision = useCallback((revision: Revision) => {
        if (
            confirm(
                translate(
                    'confirm.deleteRevision',
                    'Do you really want to delete revision {revisionDate} by {creator}?',
                    {
                        revisionDate: formatRevisionDate(revision),
                        creator: revision.creator,
                    }
                )
            )
        ) {
            fetchFromBackend({ action: 'delete', params: { revision } }, setIsLoading)
                .then(() => {
                    addFlashMessage(
                        translate('success.revisionDeleted'),
                        `Revision "${revision.label || formatRevisionDate(revision)}" by "${
                            revision.creator
                        }" deleted.`,
                        'success'
                    );
                    setMessage('');
                    fetchRevisions();
                })
                .catch((error) => {
                    setMessage(translate('error.failedDeletingRevision'));
                    console.error(error.message);
                });
        }
    }, []);

    const updateSelectedRevision = useCallback(
        (label) => {
            fetchFromBackend({ action: 'setlabel', params: { revision: selectedRevision, label } }, setIsLoading)
                .then(() => {
                    addFlashMessage(
                        translate('success.revisionUpdated'),
                        `Revision ${selectedRevision.label || formatRevisionDate(selectedRevision)} by "${
                            selectedRevision.creator
                        }" updated.`,
                        'success'
                    );
                    fetchRevisions();
                })
                .catch((error) => {
                    setMessage(translate('error.failedUpdatingRevision'));
                    console.error(error.message);
                });
        },
        [selectedRevision]
    );

    useEffect(fetchRevisions, [documentNode]);

    return (
        <div>
            {message && (
                <div style={{ color: 'red', margin: '1rem 0' }} role="alert">
                    {message}
                </div>
            )}
            {isLoading && (
                <div>
                    <Icon icon="spinner" spin color="primaryBlue" /> Loading …
                </div>
            )}
            {selectedRevision ? (
                <RevisionDetails
                    revision={selectedRevision}
                    onUpdate={updateSelectedRevision}
                    onClose={() => setSelectedRevision(null)}
                    translate={translate}
                />
            ) : revisions.length ? (
                <table style={{ width: '100%' }}>
                    <thead>
                        <tr>
                            <th style={{ textAlign: 'left' }}>{translate('header.label')}</th>
                            <th style={{ width: '100px' }}>{translate('header.actions')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {revisions.map((revision) => (
                            <tr key={revision.creationDateTime}>
                                <td title={`Created on ${formatRevisionDate(revision)} by ${revision.creator}`}>
                                    {revision.label || formatRevisionDate(revision)}
                                </td>
                                <td style={{ textAlign: 'center' }}>
                                    <IconButton
                                        onClick={() => applyRevision(revision)}
                                        icon="check"
                                        style="primary"
                                        hoverStyle="success"
                                        size="small"
                                        title={`Apply revision ${formatRevisionDate(revision)} by ${revision.creator}`}
                                    />
                                    <IconButton
                                        onClick={() => setSelectedRevision(revision)}
                                        icon="edit"
                                        style="primary"
                                        hoverStyle="warn"
                                        size="small"
                                        title={`Edit revision ${formatRevisionDate(revision)} by ${revision.creator}`}
                                    />
                                    <IconButton
                                        onClick={() => deleteRevision(revision)}
                                        icon="trash-alt"
                                        style="primary"
                                        hoverStyle="error"
                                        size="small"
                                        title={`Delete revision ${formatRevisionDate(revision)} by ${revision.creator}`}
                                    />
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            ) : (
                <em>{translate('list.noRevisionsFound')}</em>
            )}
        </div>
    );
};

export default React.memo(RevisionList);
