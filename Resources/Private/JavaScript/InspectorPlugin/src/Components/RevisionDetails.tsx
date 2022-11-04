import React, { useState } from 'react';
import { Button, TextInput } from '@neos-project/react-ui-components';

import { formatRevisionDate } from '../Helpers/format';
import Revision from '../Interfaces/Revision';
import I18nRegistry from '../Interfaces/I18nRegistry';

interface Props {
    revision: Revision;
    onUpdate: (label: string) => void;
    onClose: () => void;
    translate: I18nRegistry['translate'];
    isLoading: boolean;
}

const RevisionDetails: React.FC<Props> = ({ revision, onUpdate, onClose, translate, isLoading }) => {
    const [label, setLabel] = useState(revision.label || '');

    return (
        <div>
            <table style={{ width: '100%' }}>
                <tbody>
                    <tr>
                        <td style={{ verticalAlign: 'top' }}>
                            <strong>{translate('header.revision')}</strong>
                        </td>
                        <td>{formatRevisionDate(revision)}</td>
                    </tr>
                    <tr>
                        <td style={{ verticalAlign: 'top' }}>
                            <strong>{translate('header.creator')}</strong>
                        </td>
                        <td>{revision.creator}</td>
                    </tr>
                    {revision.isMoved && (
                        <tr>
                            <td style={{ verticalAlign: 'top' }}>
                                <strong>{translate('header.moved')}</strong>
                            </td>
                            <td>{translate('revision.isMoved')}</td>
                        </tr>
                    )}
                    <tr>
                        <td style={{ verticalAlign: 'top' }}>
                            <strong>{translate('header.identifier')}</strong>
                        </td>
                        <td>{revision.identifier}</td>
                    </tr>
                    <tr>
                        <td colSpan={2}>
                            <strong>{translate('header.label')}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td colSpan={2}>
                            <TextInput defaultValue={label} onChange={setLabel} />
                        </td>
                    </tr>
                </tbody>
            </table>
            <div style={{ marginTop: '1rem' }}>
                <Button style="warn" onClick={onClose} disabled={isLoading}>
                    {translate('action.close')}
                </Button>
                <Button style="success" onClick={() => onUpdate(label)} disabled={label == revision.label || isLoading}>
                    {translate('action.apply')}
                </Button>
            </div>
        </div>
    );
};

export default React.memo(RevisionDetails);
