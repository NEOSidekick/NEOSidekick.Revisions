import React from 'react';
import { IconButton } from '@neos-project/react-ui-components';

import { formatRevisionDate } from '../Helpers/format';
import Revision from '../Interfaces/Revision';

type RevisionListItemProps = {
    allowApply: boolean;
    revision: Revision;
    showDeleteButton: boolean;
    translate: (id: string, fallback: string, params?: Record<string, unknown> | string[]) => string;
    showRevision: (revision: Revision) => void;
    deleteRevision: (revision: Revision) => void;
    setSelectedRevision: (revision: Revision) => void;
};

const RevisionListItem: React.FC<RevisionListItemProps> = ({
    revision,
    translate,
    showRevision,
    setSelectedRevision,
    deleteRevision,
    showDeleteButton,
    allowApply,
}) => {
    return (
        <tr key={revision.creationDateTime}>
            <td
                title={translate('tooltip.revisionLabel', 'Created on {revisionDate} by {creator}', {
                    revisionDate: formatRevisionDate(revision),
                    creator: revision.creator,
                })}
            >
                <div>
                    {revision.label ||
                        translate('revision.label', 'By {creator}', {
                            creator: revision.creator,
                        })}
                </div>
                <time style={{ opacity: 0.5 }}>{formatRevisionDate(revision)}</time>
            </td>
            <td style={{ textAlign: 'center', verticalAlign: 'middle' }}>
                <IconButton
                    onClick={() => showRevision(revision)}
                    icon="trash-restore"
                    style="primary"
                    hoverStyle="success"
                    size="small"
                    disabled={!allowApply}
                    title={allowApply ? translate('action.apply.title', 'Apply revision {revisionDate} by {creator}', {
                        revisionDate: formatRevisionDate(revision),
                        creator: revision.creator,
                    }) : translate('action.apply.disabled.title', 'This revision cannot be applied')}
                />
                <IconButton
                    onClick={() => setSelectedRevision(revision)}
                    icon="comment"
                    style="primary"
                    hoverStyle="warn"
                    size="small"
                    title={translate('action.edit.title', 'Edit revision {revisionDate} by {creator}', {
                        revisionDate: formatRevisionDate(revision),
                        creator: revision.creator,
                    })}
                />
                {showDeleteButton && (
                    <IconButton
                        onClick={() => deleteRevision(revision)}
                        icon="times-circle"
                        style="primary"
                        hoverStyle="error"
                        size="small"
                        title={translate('action.delete.title', 'Delete revision {revisionDate} by {creator}', {
                            revisionDate: formatRevisionDate(revision),
                            creator: revision.creator,
                        })}
                    />
                )}
            </td>
        </tr>
    );
};

export default React.memo(RevisionListItem);
