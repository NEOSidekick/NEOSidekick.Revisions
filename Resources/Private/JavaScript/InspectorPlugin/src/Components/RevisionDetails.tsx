import React, { useCallback, useState } from 'react';
import { Button, TextInput } from '@neos-project/react-ui-components';

import { formatRevisionDate } from '../Helpers/format';
import Revision from '../Interfaces/Revision';
import I18nRegistry from '../Interfaces/I18nRegistry';

interface Props {
    revision: Revision;
    onUpdate: (label: string) => void;
    onClose: () => void;
    translate: I18nRegistry['translate'];
}

const RevisionDetails: React.FC<Props> = ({ revision, onUpdate, onClose, translate }) => {
    const [label, setLabel] = useState(revision.label || '');

    return (
        <div>
            <table style={{ width: '100%' }}>
                <tbody>
                    <tr>
                        <td>
                            <strong>{translate('header.revision')}</strong>
                        </td>
                        <td>{formatRevisionDate(revision)}</td>
                    </tr>
                    <tr>
                        <td>
                            <strong>{translate('header.creator')}</strong>
                        </td>
                        <td>{revision.creator}</td>
                    </tr>
                    <tr>
                        <td>
                            <strong>{translate('header.identifier')}</strong>
                        </td>
                        <td>{revision.identifier}</td>
                    </tr>
                    <tr>
                        <td>
                            <strong>{translate('header.label')}</strong>
                        </td>
                        <td>
                            <TextInput defaultValue={label} onChange={setLabel} />
                        </td>
                    </tr>
                </tbody>
            </table>
            <div style={{ marginTop: '1rem' }}>
                <Button style="warn" onClick={onClose}>
                    {translate('action.close')}
                </Button>
                <Button style="success" onClick={() => onUpdate(label)} disabled={label == revision.label}>
                    {translate('action.apply')}
                </Button>
            </div>
        </div>
    );
};

export default React.memo(RevisionDetails);
