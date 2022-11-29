import React from 'react';

type DiffProps = {
    original: string;
    changed: string;
};

const FallbackPropertyDiff: React.FC<DiffProps> = ({ original, changed }) => {
    return (
        <tr>
            <td>
                {original}
            </td>
            <td>
                {changed}
            </td>
        </tr>
    );
};

export default React.memo(FallbackPropertyDiff);
