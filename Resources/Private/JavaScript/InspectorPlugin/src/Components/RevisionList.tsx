import React, { useCallback, useEffect, useState } from 'react';
import { IconButton, Button, TextInput } from '@neos-project/react-ui-components';

import Node from '../Interfaces/Node';
import Revision from '../Interfaces/Revision';
import fetchFromBackend from '../Api/fetch';
import { formatRevisionDate } from '../Helpers/format';
import I18nRegistry from '../Interfaces/I18nRegistry';

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
    const [label, setLabel] = useState('');

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
        fetchFromBackend({ action: 'get', params: { node: documentNode } })
            .then(({ revisions }: { revisions: Revision[] }) => setRevisions(revisions))
            .catch((error) => {
                setMessage(translate('error.failedFetchingExpressions'));
                console.error(error.message);
            });
    }, [documentNode]);

    const applyRevision = useCallback((revision: Revision) => {
        if (confirm(`Do you want to apply revision ${formatRevisionDate(revision)} by ${revision.creator}?`)) {
            fetchFromBackend({ action: 'apply', params: { node: documentNode, revision } })
                .then(() => {
                    addFlashMessage(
                        translate('success.revisionApplied'),
                        `Revision "${revision.label || formatRevisionDate(revision)}" by ${revision.creator} applied.`,
                        'success'
                    );
                    reloadDocument();
                    setMessage('');
                })
                .catch((error) => {
                    setMessage(translate('error.failedApplyingRevision'));
                    console.error(error.message);
                });
        }
    }, []);

    const updateSelectedRevision = useCallback(() => {
        fetchFromBackend({ action: 'setlabel', params: { revision: selectedRevision, label } })
            .then(() => {
                addFlashMessage(
                    translate('success.revisionUpdated'),
                    `Revision ${selectedRevision.label || formatRevisionDate(selectedRevision)} by ${
                        selectedRevision.creator
                    } updated.`,
                    'success'
                );
                fetchRevisions();
            })
            .catch((error) => {
                setMessage(translate('error.failedUpdatingRevision'));
                console.error(error.message);
            });
    }, [selectedRevision, label]);

    useEffect(fetchRevisions, [documentNode]);

    return (
        <div>
            {message && (
                <div style={{ color: 'red', margin: '1rem 0' }} role="alert">
                    {message}
                </div>
            )}
            {selectedRevision ? (
                <div>
                    <table style={{ width: '100%' }}>
                        <tbody>
                            <tr>
                                <td>
                                    <strong>{translate('header.revision')}</strong>
                                </td>
                                <td>{formatRevisionDate(selectedRevision)}</td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>{translate('header.creator')}</strong>
                                </td>
                                <td>{selectedRevision.creator}</td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>{translate('header.identifier')}</strong>
                                </td>
                                <td>{selectedRevision.identifier}</td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>{translate('header.label')}</strong>
                                </td>
                                <td>
                                    <TextInput
                                        defaultValue={selectedRevision.label || ''}
                                        onChange={(e) => setLabel(e.target.value)}
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div style={{ marginTop: '1rem' }}>
                        <Button style="warn" onClick={() => setSelectedRevision(null)}>
                            {translate('action.close')}
                        </Button>
                        <Button
                            style="success"
                            onClick={updateSelectedRevision}
                            disabled={label == selectedRevision.label}
                        >
                            {translate('action.apply')}
                        </Button>
                    </div>
                </div>
            ) : (
                <table style={{ width: '100%' }}>
                    <thead>
                        <tr>
                            <th style={{ textAlign: 'left' }}>{translate('header.label')}</th>
                            <th>{translate('header.actions')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {revisions.length ? (
                            revisions.map((revision) => (
                                <tr key={revision.creationDateTime}>
                                    <td title={`Created on ${formatRevisionDate(revision)} by ${revision.creator}`}>
                                        {revision.label || formatRevisionDate(revision)}
                                    </td>
                                    <td style={{ textAlign: 'center' }}>
                                        <IconButton
                                            onClick={() => setSelectedRevision(revision)}
                                            icon="edit"
                                            style="primary"
                                            size="small"
                                            title={`Edit revision ${formatRevisionDate(revision)} by ${
                                                revision.creator
                                            }`}
                                        />
                                        <IconButton
                                            onClick={() => applyRevision(revision)}
                                            icon="check"
                                            style="primary"
                                            size="small"
                                            title={`Apply revision ${formatRevisionDate(revision)} by ${
                                                revision.creator
                                            }`}
                                        />
                                    </td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan={2}>
                                    <em>{translate('list.noRevisionsFound')}</em>
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            )}
        </div>
    );
};

export default React.memo(RevisionList);
