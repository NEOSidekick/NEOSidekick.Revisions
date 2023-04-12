import React from 'react';
import { formatChangeDate } from '../../Helpers/format';

type DiffProps = {
    original: string;
    changed: string;
};

const DateTimePropertyDiff: React.FC<DiffProps> = ({ original, changed }) => {
    return (
        <tr>
            <td>
                <time>{original ? formatChangeDate(original) : '-'}</time>
            </td>
            <td>
                <time>{changed ? formatChangeDate(changed) : '-'}</time>
            </td>
        </tr>
    );
};

export default React.memo(DateTimePropertyDiff);
