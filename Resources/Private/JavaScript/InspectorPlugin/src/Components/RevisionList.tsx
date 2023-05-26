import React, { useCallback, useEffect, useState } from 'react';
import { Icon } from '@neos-project/react-ui-components';

import Node from '../Interfaces/Node';
import Revision from '../Interfaces/Revision';
import fetchFromBackend from '../Api/fetch';
import { formatRevisionDate } from '../Helpers/format';
import I18nRegistry from '../Interfaces/I18nRegistry';
import RevisionDetails from './RevisionDetails';
import RevisionDiff from './RevisionDiff';
import RevisionListItem from './RevisionListItem';

interface Props {
    documentNode: Node;
    addFlashMessage: (title: string, message: string, severity?: string, timeout?: number) => void;
    reloadDocument: () => void;
    i18nRegistry: I18nRegistry;
    frontendConfiguration: {
        [key: string]: any;
        showDeleteButton: boolean;
    };
    renderSecondaryInspector: (identifier: string, component: () => React.ReactNode) => void;
    contentDimensions: ContentDimensions;
}

const RevisionList: React.FC<Props> = ({
    documentNode,
    addFlashMessage,
    reloadDocument,
    i18nRegistry,
    frontendConfiguration,
    renderSecondaryInspector,
    contentDimensions,
}) => {
    const [revisions, setRevisions] = useState<Revision[]>([]);
    const [message, setMessage] = useState('');
    const [selectedRevision, setSelectedRevision] = useState<Revision>(null);
    const [isLoading, setIsLoading] = useState(true);

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
        fetchFromBackend<{ revisions: Revision[] }>({ action: 'get', params: { node: documentNode } }, setIsLoading)
            .then(({ revisions }) => setRevisions(revisions))
            .catch((error) => {
                setMessage(translate('error.failedFetchingRevisions'));
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
                    translate('success.revisionApplied.message', 'Revision "{label}" by "{creator}" applied.', {
                        label: revision.label || formatRevisionDate(revision),
                        creator: revision.creator,
                    }),
                    'success'
                );
                reloadDocument();
                setMessage('');
            })
            .catch((error) => {
                const { status, conflicts } = error;
                if (status === 409) {
                    resolveConflicts(revision, conflicts);
                } else {
                    setMessage(translate('error.failedApplyingRevision'));
                    console.error(error);
                }
            });
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
                        translate('success.revisionDeleted.message', 'Revision "{label}" by "{creator} deleted.', {
                            label: revision.label || formatRevisionDate(revision),
                            creator: revision.creator,
                        }),
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
                        translate('success.revisionUpdated.message', 'Revision {label} by "{creator}" updated.', {
                            label: selectedRevision.label || formatRevisionDate(selectedRevision),
                            creator: selectedRevision.creator,
                        }),
                        'success'
                    );
                    setSelectedRevision(null);
                    fetchRevisions();
                })
                .catch((error) => {
                    setMessage(translate('error.failedUpdatingRevision'));
                    console.error(error.message);
                });
        },
        [selectedRevision]
    );

    const showRevision = useCallback((revision?: Revision) => {
        if (!revision) {
            renderSecondaryInspector(null, null);
        } else {
            renderSecondaryInspector('REVISIONS_COMPARE', () => (
                <RevisionDiff
                    documentNode={documentNode}
                    translate={translate}
                    revision={revision}
                    onClose={showRevision}
                    applyRevision={applyRevision}
                    contentDimensions={contentDimensions}
                />
            ));
        }
    }, []);

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
                    isLoading={isLoading}
                />
            ) : revisions.length ? (
                <table style={{ width: '100%', maxWidth: '100%' }}>
                    <thead>
                        <tr>
                            <th style={{ textAlign: 'left' }}>{translate('header.label')}</th>
                            <th style={{ width: '100px' }}>{translate('header.actions')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {revisions.map((revision, index) => (
                            <React.Fragment key={index}>
                                <RevisionListItem
                                    allowApply={index > 0 && !revision.isEmpty}
                                    revision={revision}
                                    translate={translate}
                                    showDeleteButton={frontendConfiguration.showDeleteButton}
                                    deleteRevision={deleteRevision}
                                    setSelectedRevision={setSelectedRevision}
                                    showRevision={showRevision}
                                />
                                {index < revisions.length - 1 && (
                                    <tr>
                                        <td colSpan={2} style={{ borderBottom: '1px solid #3f3f3f' }} />
                                    </tr>
                                )}
                            </React.Fragment>
                        ))}
                    </tbody>
                </table>
            ) : !isLoading ? (
                <em>{translate('list.noRevisionsFound')}</em>
            ) : (
                ''
            )}
        </div>
    );
};

export default React.memo(RevisionList);
